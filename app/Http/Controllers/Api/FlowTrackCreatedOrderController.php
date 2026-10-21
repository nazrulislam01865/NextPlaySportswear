<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\OrderProductSnapshotFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Creates a customer-visible, unpaid order for a pre-existing FlowTracker job.
 * No callbacks to FlowTracker's order-create receiver: the existing job is linked by ID.
 */
final class FlowTrackCreatedOrderController extends Controller
{
    public function store(Request $request, OrderProductSnapshotFactory $snapshots): JsonResponse
    {
        $data = $request->validate([
            'dry_run' => ['sometimes', 'boolean'],
            'flow_job_id' => ['required', 'integer', 'min:1'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'customer_email' => ['required', 'email', 'max:255'],
            'reference' => ['nullable', 'string', 'max:200'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.address' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:25'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.fabric_key' => ['nullable', 'string', 'max:100'],
            'items.*.option_value_ids' => ['sometimes', 'array', 'max:60'],
            'items.*.option_value_ids.*' => ['integer', 'min:1', 'distinct'],
            'items.*.sizes' => ['sometimes', 'array', 'max:100'],
            'items.*.sizes.*.size_id' => ['required', 'integer', 'min:1'],
            'items.*.sizes.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $key = 'flowtrack:'.(int) $data['flow_job_id'];
        // A source job maps to exactly one NextPlay order, including after retries.
        $existing = Order::query()->where('idempotency_key', $key)->first();
        if ($existing && empty($data['dry_run'])) {
            abort_unless((int) $existing->user_id === (int) $data['customer_id']
                && (int) $existing->flowtrack_job_id === (int) $data['flow_job_id'], 409,
                'The source order reference is already linked to a different customer.');
            return response()->json(['order' => $this->result($existing), 'replayed' => true]);
        }

        $customer = User::query()->whereKey($data['customer_id'])->where('role', 'customer')->where('is_active', true)->first();
        if (! $customer || ! hash_equals(mb_strtolower($customer->email), mb_strtolower($data['customer_email']))) {
            throw ValidationException::withMessages(['customer_id' => 'Select a currently active, linked NextPlay customer.']);
        }
        $ids = collect($data['items'])->pluck('product_id')->unique();
        $products = Product::query()->with(['optionGroups.values', 'sizeGroups.sizes', 'priceTiers', 'fabricPriceTables.tiers'])
            ->whereIn('id', $ids)->get()->keyBy('id');
        $lines = [];
        foreach ($data['items'] as $i => $item) {
            $p = $products->get($item['product_id']);
            if (! $p || $p->status !== 'active' || ! $p->is_active || $p->deleted_at) {
                throw ValidationException::withMessages(["items.$i.product_id" => 'Product is not currently available in NextPlay.']);
            }
            $qty = (int) $item['quantity'];
            if ($qty < (int) $p->minimum_quantity || ($p->maximum_quantity && $qty > (int) $p->maximum_quantity)) {
                throw ValidationException::withMessages(["items.$i.quantity" => 'Quantity is outside this product’s allowed range.']);
            }
            // Complex roster/artwork and file-based choices require dedicated private
            // attachment transfer. Never silently create an incomplete customer order.
            if ($p->artwork_upload_required || ($p->jersey_roster_enabled && ! $p->jersey_roster_optional)) {
                throw ValidationException::withMessages(["items.$i.product_id" => 'This product requires artwork or roster data. Finish this configuration in NextPlay until its private transfer flow is enabled.']);
            }
            $selected = collect($item['option_value_ids'] ?? [])->map(fn ($v) => (int) $v);
            $options = [];
            foreach ($p->optionGroups->where('is_active', true) as $group) {
                $allowedValues = $group->values->where('is_active', true);
                $matches = $allowedValues->whereIn('id', $selected->all());
                $minimum = max((int) ($group->minimum_selections ?? 0), $group->is_required ? 1 : 0);
                $maximum = max(1, (int) ($group->maximum_selections ?? 1));
                $type = strtolower((string) ($group->type ?? 'select'));
                if (in_array($type, ['file', 'upload', 'text', 'textarea', 'number', 'date'], true)) {
                    throw ValidationException::withMessages(["items.$i.option_value_ids" => "{$group->name} needs a NextPlay-specific input unavailable in this order form."]);
                }
                if ($minimum > 1 || $maximum > 1) {
                    throw ValidationException::withMessages(["items.$i.option_value_ids" => "{$group->name} supports multiple selections; complete it in NextPlay until multi-select is enabled."]);
                }
                if ($matches->count() < $minimum || $matches->count() > $maximum) {
                    throw ValidationException::withMessages(["items.$i.option_value_ids" => "Select the allowed {$group->name} option in NextPlay configuration."]);
                }
                foreach ($matches as $v) $options[] = ['group_id' => $group->id, 'value_id' => $v->id, 'label' => $v->label];
            }
            if (count($options) !== $selected->count()) {
                throw ValidationException::withMessages(["items.$i.option_value_ids" => 'Unrecognized product option. Refresh the configuration.']);
            }
            $sizes = collect($item['sizes'] ?? []);
            $allowedSizes = $p->sizeGroups->where('is_active', true)->flatMap(fn ($g) => $g->sizes->where('is_active', true))->pluck('id')->map(fn ($id) => (int) $id);
            if (($allowedSizes->isEmpty() && $sizes->isNotEmpty()) || ($allowedSizes->isNotEmpty() && ($sizes->isEmpty() || $sizes->sum('quantity') !== $qty || $sizes->pluck('size_id')->unique()->count() !== $sizes->count() || $sizes->contains(fn ($s) => ! $allowedSizes->contains((int) $s['size_id']))))) {
                throw ValidationException::withMessages(["items.$i.sizes" => 'Select valid sizes adding up to the ordered quantity.']);
            }
            $tables = $p->fabricPriceTables->where('is_active', true);
            $fabric = trim((string) ($item['fabric_key'] ?? ''));
            if ($tables->isNotEmpty()) {
                if ($fabric === '' && $tables->count() === 1) $fabric = (string) $tables->first()->fabric_key;
                $table = $tables->firstWhere('fabric_key', $fabric);
                if (! $table) throw ValidationException::withMessages(["items.$i.fabric_key" => 'Select a valid fabric to calculate the price.']);
                $tiers = $table->tiers;
            } else {
                $tiers = $p->priceTiers;
            }
            $tier = $tiers->filter(fn ($t) => $qty >= (int) $t->minimum_quantity && (!$t->maximum_quantity || $qty <= (int) $t->maximum_quantity))->sortByDesc('minimum_quantity')->first();
            if ($tiers->isNotEmpty() && ! $tier) throw ValidationException::withMessages(["items.$i.quantity" => 'No active price tier covers this quantity.']);
            $price = $tier ? (float) $tier->unit_price : (float) $p->base_price;
            if ($price <= 0) throw ValidationException::withMessages(["items.$i.product_id" => 'This product needs a configured selling price in NextPlay.']);
            $lines[] = compact('p', 'qty', 'price', 'options', 'sizes', 'fabric') + ['notes' => $item['notes'] ?? null];
        }

        if (! empty($data['dry_run'])) {
            return response()->json(['valid' => true, 'currency' => 'USD', 'subtotal' => round(collect($lines)->sum(fn ($l) => $l['price'] * $l['qty']), 2),
                'unit_prices' => collect($lines)->pluck('price')->values()->all(),
                'pricing_note' => 'Provisional catalog-tier amounts; shipping, promotions, customization surcharges and tax require manual invoice review.']);
        }

        // The idempotency key is uniquely constrained in the orders table.
        // Concurrent attempts serialize through the insert/unique constraint; a
        // collision returns the committed order, never a second customer order.
        try {
            $order = DB::transaction(function () use ($data, $key, $customer, $lines, $snapshots): Order {
                $subtotal = round(collect($lines)->sum(fn ($l) => $l['price'] * $l['qty']), 2);
                do {
                    $number = 'NP-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
                } while (Order::query()->where('order_number', $number)->exists());
                $order = Order::create([
                    'user_id' => $customer->id, 'order_number' => $number,
                    'status' => 'quote_invoice_requested', 'payment_status' => 'pending',
                    'fulfillment_status' => 'unfulfilled', 'currency' => 'USD',
                    'customer_name' => $customer->name, 'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone, 'subtotal' => $subtotal,
                    'customization_total' => 0, 'discount_total' => 0,
                    'shipping_total' => 0, 'rural_surcharge_total' => 0,
                    'product_shipping_total' => 0, 'tax_total' => 0, 'grand_total' => $subtotal,
                    'total_quantity' => collect($lines)->sum('qty'),
                    'information' => ['source' => 'flowtrack', 'flow_job_id' => (int) $data['flow_job_id'], 'reference' => $data['reference'] ?? null, 'pricing' => 'catalog_tier_estimate_pending_manual_invoice_review'],
                    'shipping_address' => $data['shipping_address'], 'billing_address' => $data['shipping_address'],
                    'shipping_method' => ['method' => 'admin_arranged', 'label' => 'Shipping to be confirmed'],
                    'payment_method' => ['method' => 'manual_invoice', 'payment_type' => 'invoice', 'requires_manual_review' => true],
                    'idempotency_key' => $key, 'placed_at' => now(),
                    'flowtrack_job_id' => (string) $data['flow_job_id'], 'flowtrack_sync_status' => 'synced',
                    'flowtrack_synced_at' => now(),
                ]);
                foreach ($lines as $l) {
                    $p = $l['p'];
                    $customization = [
                        'configuration' => ['fabric_key' => $l['fabric'], 'selected_option_values' => $l['options']],
                        'size_breakdown' => $l['sizes']->map(fn ($size) => ['size_id' => (int) $size['size_id'], 'quantity' => (int) $size['quantity']])->all(),
                        'notes' => $l['notes'],
                    ];
                    $snapshot = $snapshots->make($p, $customization, [
                        'quantity' => $l['qty'], 'unit_price' => $l['price'], 'line_total' => round($l['price'] * $l['qty'], 2),
                    ], 'flowtrack_admin_order');
                    $order->items()->create([
                        'product_id' => $p->id, 'product_slug' => $p->slug,
                        'product_name' => $p->name, 'sku' => $p->sku,
                        'image_url' => $p->primaryImageUrl(), 'quantity' => $l['qty'],
                        'unit_price' => $l['price'], 'customization_unit_price' => 0,
                        'line_total' => round($l['price'] * $l['qty'], 2),
                        'customization' => $customization,
                        'product_snapshot_schema_version' => OrderProductSnapshotFactory::SCHEMA_VERSION,
                        'product_snapshot' => $snapshot,
                        'resolved_customization' => (array) ($snapshot['selected_configuration'] ?? []),
                        'product_snapshot_captured_at' => now(), 'is_digital' => false,
                    ]);
                }
                return $order;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // A parallel retry must never claim an order owned by another buyer.
            $order = Order::query()->where('idempotency_key', $key)->firstOrFail();
            abort_unless((int) $order->user_id === (int) $data['customer_id']
                && (int) $order->flowtrack_job_id === (int) $data['flow_job_id'], 409,
                'The source order reference is already linked to a different customer.');
            return response()->json(['order' => $this->result($order), 'replayed' => true]);
        }
        return response()->json(['order' => $this->result($order), 'replayed' => false], 201);
    }

    private function result(Order $order): array
    {
        return ['id' => $order->id, 'order_number' => $order->order_number,
            'flow_job_id' => $order->flowtrack_job_id, 'payment_status' => $order->payment_status,
            'status' => $order->status];
    }
}
