<?php

namespace App\Services\Integrations\FlowTrack;

use App\Models\BulkQuoteRequest;
use Illuminate\Support\Facades\Storage;

final class FlowTrackInquiryPayloadFactory
{
    public const SCHEMA_VERSION = 1;

    /** @return array<string, mixed> */
    public function make(BulkQuoteRequest $quote): array
    {
        $quote->loadMissing('user');

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'source_application' => 'nextplay',
            'id' => $quote->id,
            'user_id' => $quote->user_id,
            'reference' => (string) $quote->reference,
            'status' => (string) $quote->status,

            'full_name' => (string) $quote->full_name,
            'organization' => (string) $quote->organization,
            'email' => (string) $quote->email,
            'phone' => (string) $quote->phone,
            'customer_account' => $this->customerAccount($quote),

            'product_type' => (string) $quote->product_type,
            'estimated_quantity' => (string) $quote->estimated_quantity,
            'sizes_needed' => (string) $quote->sizes_needed,
            'budget_range' => $quote->budget_range,
            'artwork_details' => (string) $quote->artwork_details,
            'customization_types' => array_values((array) ($quote->customization_types ?? [])),

            'shipping_address' => (string) $quote->shipping_address,
            'country' => (string) $quote->country,
            'state_province' => $quote->state_province,
            'postal_code' => $quote->postal_code,
            'preferred_shipping_method' => $quote->preferred_shipping_method,
            'needed_by' => $quote->needed_by?->toDateString(),
            'event_date' => $quote->event_date?->toDateString(),

            'attachment' => $this->attachment($quote),
            'additional_notes' => $quote->additional_notes,
            'idempotency_key' => $this->idempotencyKey($quote),
            'created_at' => $quote->created_at?->toIso8601String(),
            'updated_at' => $quote->updated_at?->toIso8601String(),
        ];
    }

    public function idempotencyKey(BulkQuoteRequest $quote): string
    {
        return 'nextplay-bulk-quote-'.(int) $quote->id;
    }

    /** @return array<string, mixed> */
    private function customerAccount(BulkQuoteRequest $quote): array
    {
        if (is_array($quote->customer_account) && $quote->customer_account !== []) {
            return $quote->customer_account;
        }

        $user = $quote->user;

        return [
            'is_registered' => $user !== null,
            'source_user_id' => $user?->id,
            'name' => (string) ($user?->name ?: $quote->full_name),
            'email' => (string) ($user?->email ?: $quote->email),
            'phone' => $user?->phone ?: $quote->phone,
            'company_name' => $user?->company_name,
            'preferred_sport' => $user?->preferred_sport,
        ];
    }

    /** @return array<string, mixed>|null */
    private function attachment(BulkQuoteRequest $quote): ?array
    {
        $attachment = is_array($quote->attachment) ? $quote->attachment : [];
        $path = trim((string) ($attachment['path'] ?? ''));

        if ($path === '') {
            return null;
        }

        $checksum = null;
        if (Storage::disk('public')->exists($path)) {
            $absolutePath = Storage::disk('public')->path($path);
            $hash = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : false;
            $checksum = is_string($hash) ? $hash : null;
        }

        return [
            'path' => $path,
            'original_name' => (string) ($attachment['original_name'] ?? 'bulk-quote-attachment'),
            'mime_type' => (string) ($attachment['mime_type'] ?? 'application/octet-stream'),
            'size' => max(0, (int) ($attachment['size'] ?? 0)),
            'download_path' => '/api/integrations/flowtrack/bulk-quotes/'.(int) $quote->id.'/attachment',
            'checksum_sha256' => $checksum,
        ];
    }
}
