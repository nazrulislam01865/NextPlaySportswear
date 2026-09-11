<?php

namespace App\Services\Integrations\FlowTrack;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Order\OrderProductSnapshotFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class FlowTrackOrderPayloadFactory
{
    public const SCHEMA_VERSION = 3;

    public function __construct(private readonly OrderProductSnapshotFactory $productSnapshots)
    {
    }

    /**
     * Build the complete immutable NextPlay order snapshot FlowTrack receives.
     *
     * Besides the normalized contract used by FlowTrack operational screens,
     * source_order_attributes/source_item_attributes preserve every current
     * database column from NextPlay. source_related_data preserves the other
     * order-management records so future FlowTrack screens never require the
     * source database to reconstruct what was received.
     *
     * @return array<string, mixed>
     */
    public function make(Order $order): array
    {
        $order->loadMissing([
            'user',
            'items',
            'payments',
            'histories',
            'shipments.items',
            'changeRequests',
            'returnRequests.items',
            'returnRequests.attachments',
            'returnRequests.refunds',
            'refunds.creditNote',
            'creditNotes',
            'downloads',
        ]);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'source_application' => 'nextplay',
            'id' => $order->id,
            'user_id' => $order->user_id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'payment_status' => (string) $order->payment_status,
            'fulfillment_status' => (string) $order->fulfillment_status,
            'currency' => (string) $order->currency,
            'coupon_id' => $order->coupon_id,
            'coupon_code' => $order->coupon_code,
            'coupon_snapshot' => $order->coupon_snapshot,
            'customer_name' => (string) $order->customer_name,
            'customer_email' => (string) $order->customer_email,
            'customer_phone' => $order->customer_phone,

            // Registered customer profile used by FlowTrack for the client/order
            // context. This is deliberately allow-listed: authentication, admin,
            // session and other security-sensitive user columns are never sent.
            'customer_account' => $this->customerAccountPayload($order),
            'subtotal' => (float) $order->subtotal,
            'customization_total' => (float) $order->customization_total,
            'discount_total' => (float) $order->discount_total,
            'shipping_total' => (float) $order->shipping_total,
            'rural_surcharge_total' => (float) $order->rural_surcharge_total,
            'product_shipping_total' => (float) ($order->product_shipping_total ?? 0),
            'tax_total' => (float) $order->tax_total,
            'grand_total' => (float) $order->grand_total,
            'total_quantity' => (int) $order->total_quantity,
            'information' => (array) ($order->information ?? []),
            'shipping_address' => (array) ($order->shipping_address ?? []),
            'billing_address' => (array) ($order->billing_address ?? []),
            'shipping_method' => (array) ($order->shipping_method ?? []),
            'payment_method' => (array) ($order->payment_method ?? []),
            'customer_note' => $order->customer_note,
            'admin_note' => $order->admin_note,
            'idempotency_key' => $order->idempotency_key,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'deleted_at' => $order->deleted_at?->toIso8601String(),

            // Future-proof copy of every persisted orders-table attribute.
            'source_order_attributes' => $order->attributesToArray(),

            // Current order-management records. These can be empty at initial
            // checkout, but a manual resend still preserves what exists then.
            'source_related_data' => $this->relatedData($order),

            'items' => $order->items
                ->map(fn (OrderItem $item): array => $this->itemPayload($item))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function customerAccountPayload(Order $order): array
    {
        $user = $order->user;

        return [
            'is_registered' => $user !== null,
            'source_user_id' => $user?->id,
            'name' => (string) ($user?->name ?: $order->customer_name),
            'email' => (string) ($user?->email ?: $order->customer_email),
            'phone' => $user?->phone ?: $order->customer_phone,
            'company_name' => $user?->company_name,
            'preferred_sport' => $user?->preferred_sport,

            // Keep the contact details actually used for this order separately.
            // A customer may update their account profile after placing an order,
            // while the order contact must remain an immutable transaction record.
            'order_contact' => [
                'name' => (string) $order->customer_name,
                'email' => (string) $order->customer_email,
                'phone' => $order->customer_phone,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function itemPayload(OrderItem $item): array
    {
        $customization = (array) ($item->customization ?? []);
        $productSnapshot = $this->ensureProductSnapshot($item, $customization);
        $resolvedCustomization = (array) ($item->resolved_customization ?? data_get($productSnapshot, 'selected_configuration', []));
        $artworkFiles = collect($item->artworkFiles())
            ->values()
            ->map(function (array $file, int $index) use ($item): array {
                $path = (string) ($file['path'] ?? '');
                $downloadPath = '/api/integrations/flowtrack/order-items/'.(int) $item->id.'/artworks/'.$index;
                $checksum = null;

                if ($path !== '' && Storage::disk('local')->exists($path)) {
                    $absolutePath = Storage::disk('local')->path($path);
                    $checksum = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null;
                }

                return [
                    'path' => $path,
                    'download_path' => $downloadPath,
                    'source_url' => url($downloadPath),
                    'original_name' => (string) ($file['original_name'] ?? 'Artwork file'),
                    'size' => max(0, (int) ($file['size'] ?? 0)),
                    'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
                    'checksum_sha256' => $checksum,
                ];
            })
            ->all();

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_slug' => $item->product_slug,
            'product_name' => (string) $item->product_name,
            'sku' => $item->sku,
            'image_url' => $this->absoluteUrl($item->image_url),
            'quantity' => (int) $item->quantity,
            'fulfilled_quantity' => (int) ($item->fulfilled_quantity ?? 0),
            'cancelled_quantity' => (int) ($item->cancelled_quantity ?? 0),
            'returned_quantity' => (int) ($item->returned_quantity ?? 0),
            'unit_price' => (float) $item->unit_price,
            'customization_unit_price' => (float) ($item->customization_unit_price ?? 0),
            'line_total' => (float) $item->line_total,
            'customization' => $customization,
            'product_snapshot_schema_version' => (int) ($item->product_snapshot_schema_version ?: OrderProductSnapshotFactory::SCHEMA_VERSION),
            'product_snapshot' => $productSnapshot,
            'resolved_customization' => $resolvedCustomization,
            'product_snapshot_captured_at' => $item->product_snapshot_captured_at?->toIso8601String(),
            'is_digital' => (bool) $item->is_digital,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),

            // Explicit normalized copies of rich customization data. FlowTrack
            // stores these in dedicated tables instead of forcing users to dig
            // through one JSON column.
            'design_option' => data_get($customization, 'design_option'),
            'delivery_preference' => data_get($customization, 'delivery_preference'),
            'size_summary' => data_get($customization, 'size_summary'),
            'sizes' => $item->selectedSizes(),
            'roster_fields' => $item->rosterFields(),
            'roster_rows' => $item->rosterRows(),
            'artwork_status' => data_get($customization, 'artwork_status'),
            'artwork_files' => $artworkFiles,
            'customization_notes' => data_get($customization, 'notes'),
            'configuration' => (array) data_get($customization, 'configuration', []),
            'sample' => (array) data_get($customization, 'sample', []),
            'fulfillment' => (array) data_get($customization, 'fulfillment', []),
            'selected_options' => [
                'selections' => (array) data_get($customization, 'configuration.selections', []),
                'multi_selections' => (array) data_get($customization, 'configuration.multi_selections', []),
                'inputs' => (array) data_get($customization, 'configuration.inputs', []),
                'quantities' => (array) data_get($customization, 'configuration.quantities', []),
                'production_speed' => data_get($customization, 'configuration.production_speed'),
                'shipping_method' => data_get($customization, 'configuration.shipping_method'),
                'roster_enabled' => (bool) data_get($customization, 'configuration.roster_enabled', false),
                'sample_requested' => (bool) data_get($customization, 'configuration.sample_requested', false),
                'resolved_options' => (array) ($resolvedCustomization['options'] ?? []),
                'resolved_sizes' => (array) ($resolvedCustomization['sizes'] ?? []),
                'resolved_production_speed' => $resolvedCustomization['production_speed'] ?? null,
                'resolved_shipping_method' => $resolvedCustomization['shipping_method'] ?? null,
            ],

            // Every persisted order_items-table attribute, including any future
            // columns added before FlowTrack is upgraded again.
            'source_item_attributes' => $item->attributesToArray(),
        ];
    }

    /**
     * New orders already carry the immutable snapshot. Legacy orders are
     * backfilled once from the current product record during an explicit sync,
     * then persisted into NextPlay so every later resend is historical and
     * self-contained as well.
     *
     * @param array<string,mixed> $customization
     * @return array<string,mixed>
     */
    private function ensureProductSnapshot(OrderItem $item, array $customization): array
    {
        if (is_array($item->product_snapshot) && $item->product_snapshot !== []) {
            return $item->product_snapshot;
        }

        $product = null;
        if ($item->product_id) {
            $product = Product::withTrashed()->find($item->product_id);
        }
        if (! $product && filled($item->product_slug)) {
            $product = Product::withTrashed()->where('slug', $item->product_slug)->first();
        }

        $context = [
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'customization_unit_price' => (float) ($item->customization_unit_price ?? 0),
            'line_total' => (float) $item->line_total,
        ];

        $snapshot = $product
            ? $this->productSnapshots->make($product, $customization, $context, 'flowtrack_sync_backfill_current_catalog')
            : $this->productSnapshots->makeFallback([
                'id' => $item->product_id,
                'slug' => $item->product_slug,
                'title' => $item->product_name,
                'sku' => $item->sku,
                'image' => $item->image_url,
            ], $customization, $context);

        $item->forceFill([
            'product_snapshot_schema_version' => OrderProductSnapshotFactory::SCHEMA_VERSION,
            'product_snapshot' => $snapshot,
            'resolved_customization' => (array) ($snapshot['selected_configuration'] ?? []),
            'product_snapshot_captured_at' => now(),
        ])->saveQuietly();

        return $snapshot;
    }

    /** @return array<string, mixed> */
    private function relatedData(Order $order): array
    {
        return [
            'payments' => $order->payments->map(fn (Model $model): array => $model->attributesToArray())->values()->all(),
            'status_histories' => $order->histories->map(fn (Model $model): array => $model->attributesToArray())->values()->all(),
            'shipments' => $order->shipments->map(function (Model $shipment): array {
                $data = $shipment->attributesToArray();
                $data['items'] = $shipment->items
                    ->map(fn (Model $item): array => $item->attributesToArray())
                    ->values()
                    ->all();
                return $data;
            })->values()->all(),
            'change_requests' => $order->changeRequests->map(fn (Model $model): array => $model->attributesToArray())->values()->all(),
            'return_requests' => $order->returnRequests->map(function (Model $request): array {
                $data = $request->attributesToArray();
                $data['items'] = $request->items->map(fn (Model $item): array => $item->attributesToArray())->values()->all();
                $data['attachments'] = $request->attachments->map(fn (Model $attachment): array => $attachment->attributesToArray())->values()->all();
                $data['refunds'] = $request->refunds->map(fn (Model $refund): array => $refund->attributesToArray())->values()->all();
                return $data;
            })->values()->all(),
            'refunds' => $order->refunds->map(function (Model $refund): array {
                $data = $refund->attributesToArray();
                $data['credit_note'] = $refund->creditNote?->attributesToArray();
                return $data;
            })->values()->all(),
            'credit_notes' => $order->creditNotes->map(fn (Model $model): array => $model->attributesToArray())->values()->all(),
            'downloads' => $order->downloads->map(fn (Model $model): array => $model->attributesToArray())->values()->all(),
        ];
    }

    private function absoluteUrl(?string $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return url('/'.ltrim($value, '/'));
    }
}
