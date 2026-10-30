<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Integrations\TransferFlowTrackBulkQuoteFiles;
use App\Models\BulkQuoteRequest;
use App\Models\User;
use App\Services\Shipping\RuralAreaSurchargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Restricted machine-to-machine upsert, customer-owned and replay safe. */
final class FlowTrackBulkOrderController extends Controller
{
    public function store(Request $request, RuralAreaSurchargeService $remoteAreas): JsonResponse
    {
        if (! $request->isJson()) return response()->json(['ok' => false, 'message' => 'JSON required'], 415);
        $data = $request->validate([
            'source' => ['required', Rule::in(['flowtrack', 'nextplay'])],
            'flowtrack_inquiry_id' => ['required', 'integer', 'min:1'],
            'source_quote_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['required', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'organization' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'product_type' => ['required', 'string', 'min:2', 'max:190'],
            'estimated_quantity' => ['required', Rule::in(['10-49','50-99','100-499','500-999','1000-plus'])],
            'sizes_needed' => ['required', 'string', 'max:190'],
            'budget_range' => ['nullable', 'string', 'max:80'],
            'artwork_details' => ['required', 'string', 'min:1', 'max:5000'],
            'customization_types' => ['array', 'max:6'],
            'customization_types.*' => [Rule::in(['logo','names-numbers','embroidery','sublimation','full-custom-design','not-sure'])],
            'shipping_address' => ['required', 'string', 'min:3', 'max:500'],
            'country' => ['required', 'string', 'max:80'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'preferred_shipping_method' => ['nullable', Rule::in(['standard','express','rush','recommend'])],
            'needed_by' => ['required', 'date'],
            'event_date' => ['nullable', 'date'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(BulkQuoteRequest::statuses()))],
            'selected_products' => ['sometimes', 'array', 'max:25'],
            'selected_products.*' => ['array:id,name,category,quantity,unit'],
            'selected_products.*.id' => ['required', 'integer', 'min:1'],
            'selected_products.*.name' => ['required', 'string', 'max:255'],
            'selected_products.*.category' => ['nullable', 'string', 'max:190'],
            'selected_products.*.quantity' => ['required', 'numeric', 'min:0'],
            'selected_products.*.unit' => ['nullable', 'string', 'max:40'],
            'client_files' => ['array', 'max:25'],
            'client_files.*.id' => ['required', 'integer', 'min:1'],
            'client_files.*.name' => ['required', 'string', 'max:190'],
            'client_files.*.mime_type' => ['nullable', 'string', 'max:190'],
            'client_files.*.size' => ['required', 'integer', 'min:0', 'max:20971520'],
            'client_files.*.checksum_sha256' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
            'client_files.*.download_path' => ['required', 'string', 'max:250'],
        ]);

        $sourceId = (int) $data['flowtrack_inquiry_id'];
        $isNextPlayOrigin = $data['source'] === 'nextplay';
        if ($isNextPlayOrigin && empty($data['source_quote_id'])) {
            throw ValidationException::withMessages(['source_quote_id' => 'Original NextPlay quote ID is required.']);
        }
        // The private token authenticates the application, not customer ownership.
        // Link by immutable customer ID where available, otherwise exact account email.
        $customer = ! empty($data['user_id'])
            ? User::query()->whereKey($data['user_id'])->where('role', 'customer')->where('is_active', true)->first()
            : User::query()->where('email', mb_strtolower(trim((string) $data['email'])))
                ->where('role', 'customer')->where('is_active', true)->first();
        // Original guest quotes are still editable by FlowTracker staff; they
        // remain guests and must not be silently assigned to an unrelated account.
        if ($isNextPlayOrigin && empty($data['user_id'])) $customer = null;
        if (! $customer && ! $isNextPlayOrigin) throw ValidationException::withMessages([
            'user_id' => 'Select an active, synchronized NextPlay customer or provide that customer account email.',
        ]);
        if (! $isNextPlayOrigin && $customer &&
            ! hash_equals(mb_strtolower(trim($customer->email)), mb_strtolower(trim($data['email'])))) {
            throw ValidationException::withMessages([
                'email' => 'Use the selected NextPlay customer account email when creating the linked bulk order.',
            ]);
        }
        $manifestHash = hash('sha256', json_encode($data['client_files'] ?? [], JSON_THROW_ON_ERROR));
        $quote = DB::transaction(function () use ($data, $customer, $isNextPlayOrigin, $sourceId, $manifestHash, $remoteAreas): BulkQuoteRequest {
            $query = BulkQuoteRequest::query();
            $existing = $isNextPlayOrigin
                ? $query->whereKey((int) $data['source_quote_id'])->lockForUpdate()->first()
                : $query->where('flowtrack_source_inquiry_id', $sourceId)->lockForUpdate()->first();
            if ($isNextPlayOrigin && (! $existing || (int) $existing->flowtrack_inquiry_id !== $sourceId)) {
                throw ValidationException::withMessages(['source_quote_id' => 'Original NextPlay quote link was not found or does not match.']);
            }
            if ($isNextPlayOrigin && $existing && ! hash_equals((string) $existing->reference, (string) $data['source_reference'])) {
                throw ValidationException::withMessages(['source_reference' => 'Original NextPlay quote reference does not match.']);
            }
            if ($existing && ((int) ($existing->user_id ?? 0) !== (int) ($customer?->id ?? 0)
                || (! $isNextPlayOrigin && (int) $existing->flowtrack_inquiry_id !== $sourceId))) {
                throw ValidationException::withMessages(['user_id' => 'A synchronized quote cannot be reassigned to a different customer.']);
            }
            $fields = array_intersect_key($data, array_flip([
                'full_name', 'organization', 'email', 'phone', 'product_type', 'estimated_quantity',
                'sizes_needed', 'budget_range', 'artwork_details', 'customization_types',
                'shipping_address', 'country', 'state_province', 'postal_code',
                'preferred_shipping_method', 'needed_by', 'event_date', 'additional_notes',
            ]));
            // FlowTracker corrections may update customer/request fields, but NextPlay
            // remains authoritative for quote status, pricing, publication and payments.
            if (! $existing) {
                $quote = new BulkQuoteRequest();
                $quote->reference = 'FTQ-'.str_pad((string) $sourceId, 12, '0', STR_PAD_LEFT);
                $quote->user_id = $customer->id;
                $quote->flowtrack_source_inquiry_id = $sourceId;
                $quote->flowtrack_inquiry_id = $sourceId;
                $quote->flowtrack_sync_status = BulkQuoteRequest::FLOWTRACK_SYNC_SYNCED;
                $quote->status = BulkQuoteRequest::STATUS_NEW;
                $quote->customer_account = [
                    'is_registered' => true, 'source_user_id' => $customer->id,
                    'name' => $customer->name, 'email' => $customer->email,
                    'phone' => $customer->phone,
                ];
            } else {
                $quote = $existing;
            }
            $quote->fill($fields);
            // FlowTracker's staff-selected inquiry items are separate from the
            // original customer's product picker. Preserve both without a migration.
            $productInfo = (array) ($quote->product_information ?? []);
            if (! empty($data['selected_products'])) {
                $productInfo['selected_products'] = array_values($data['selected_products']);
            } else {
                unset($productInfo['selected_products']);
            }
            $quote->product_information = $productInfo === [] ? null : $productInfo;
            if ($quote->is_remote_area === null || $quote->isDirty(['postal_code', 'country', 'state_province'])) {
                $quote->is_remote_area = filled($quote->postal_code)
                    ? $remoteAreas->resolve(
                        $quote->postal_code, $quote->country, $quote->state_province,
                    ) !== null
                    : null;
            }
            $quote->flowtrack_manifest_hash = $manifestHash;
            $quote->saveQuietly(); // Never echo an incoming synchronization event.
            return $quote;
        }, 3);

        // A separate job pulls verified bytes from the private FlowTracker API.
        TransferFlowTrackBulkQuoteFiles::dispatch($quote->id, $sourceId, $data['client_files'] ?? [], $manifestHash)
            ->onConnection((string) config('flowtrack.queue.connection', 'database'))
            ->onQueue((string) config('flowtrack.queue.name', 'integrations'))
            ->afterCommit();

        return response()->json(['ok' => true, 'quote_id' => $quote->id,
            'reference' => $quote->reference, 'user_id' => $customer?->id, 'flowtrack_inquiry_id' => $sourceId]);
    }
}
