<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BulkQuoteGuestService
{
    public function newToken(): string
    {
        return Str::random(64);
    }

    public function canAccess(BulkQuoteRequest $quote, ?string $token): bool
    {
        return $quote->user_id === null && $quote->guest_access_hash !== null
            && is_string($token) && strlen($token) === 64
            && hash_equals($quote->guest_access_hash, hash('sha256', $token));
    }

    public function storeFile(BulkQuoteRequest $quote, UploadedFile $upload, string $category, int $version, ?int $actorId = null, ?int $messageId = null): BulkQuoteFile
    {
        // Private local storage: never expose raw uploads through /storage or /media.
        $path = $upload->store('bulk-quote-private/'.$quote->id, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['files' => 'The file could not be saved. Please try again.']);
        }
        $storedFile = $quote->quoteFiles()->create([
            'bulk_quote_message_id' => $messageId,
            'uploaded_by' => $actorId,
            'version' => $version,
            'category' => $category,
            'path' => $path,
            'original_name' => Str::limit(basename($upload->getClientOriginalName()), 190, ''),
            'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
            'size' => $upload->getSize() ?: 0,
        ]);
        return $storedFile;
    }

    public function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            Storage::disk('local')->delete($file->path);
        }
    }

    /**
     * Persist customer feedback and its private attachments atomically.
     * Shared by public guest access and the authenticated account center.
     *
     * @param array<int,string> $changeTypes
     * @param array<int,UploadedFile> $uploads
     */
    public function storeCustomerMessage(BulkQuoteRequest $quote, string $body, string $kind, array $changeTypes, array $uploads, ?int $customerId = null): void
    {
        $created = [];
        try {
            DB::transaction(function () use ($quote, $body, $kind, $changeTypes, $uploads, $customerId, &$created): void {
                $quote = BulkQuoteRequest::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();
                if ($customerId !== null && (int) $quote->user_id !== $customerId) {
                    abort(404);
                }
                $revisionPending = $quote->guest_decision === 'changes_requested';
                if ((! $quote->quotation_sent_at && ! ($kind === 'message' && $revisionPending)) || $quote->quotation_version < 1 ||
                    in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED,
                        BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED], true)) {
                    throw ValidationException::withMessages(['body' => 'This quotation is not available for feedback. Please contact sales.']);
                }
                if ($kind === 'change_request' && $revisionPending) {
                    throw ValidationException::withMessages(['body' => 'A quotation revision is already in progress. You can still send a message.']);
                }
                $message = $quote->messages()->create([
                    'sender' => 'guest',
                    'actor_id' => $customerId,
                    'kind' => $kind,
                    'body' => $body,
                    'change_types' => $changeTypes ?: null,
                ]);
                foreach ($uploads as $upload) {
                    $created[] = $this->storeFile($quote, $upload, 'feedback', $quote->quotation_version, $customerId, $message->id);
                }
                if ($kind === 'change_request') {
                    // Keep prior quotation bytes for history, never publish an old
                    // price or offer as the new version after a change request.
                    $quote->quoteFiles()->where('category', 'quotation')->update(['category' => 'quotation_archived']);
                    $quote->quoteFiles()->where('category', 'flowtrack_offer')->update(['category' => 'offer_archived']);
                    $quote->forceFill([
                        'last_change_request_id' => $message->id,
                        'quotation_details' => null,
                        'quotation_sent_at' => null,
                        'quotation_valid_until' => null,
                        'quoted_amount' => null,
                        'flowtrack_offer_manifest_hash' => null,
                        'flowtrack_offer_ready_hash' => null,
                        'flowtrack_offer_file_count' => 0,
                        'flowtrack_task_progress' => [],
                    ]);
                }
                $quote->forceFill([
                    'status' => BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED,
                    'guest_decision' => $kind === 'change_request' ? 'changes_requested' : $quote->guest_decision,
                ])->saveQuietly(); // the message observer dispatches one complete post-commit sync

            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($created);
            throw $exception;
        }
    }

    public function accept(BulkQuoteRequest $quote, string $decision, ?User $customer = null): BulkQuoteRequest
    {
        $wasLinkedToAccount = $quote->user_id !== null;
        $result = DB::transaction(function () use ($quote, $decision, $customer): BulkQuoteRequest {
            $quote = BulkQuoteRequest::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($decision === 'create_order') {
                // A secure tracking link is not proof of account ownership. Require an
                // active customer with the same email before linking any records.
                if (! $customer?->isCustomer() || ! $customer->is_active ||
                    (config('security.email_verification.enabled', false) && ! $customer->hasVerifiedEmail())) {
                    throw ValidationException::withMessages(['quote' => 'Create or sign in to a verified NextPlay customer account before placing an order.']);
                }
                if (! hash_equals(strtolower(trim($quote->email)), strtolower(trim($customer->email))) ||
                    ($quote->user_id !== null && (int) $quote->user_id !== (int) $customer->id)) {
                    throw ValidationException::withMessages(['quote' => 'Sign in with the customer account using the same email as this quotation.']);
                }
                // Orders created under the earlier guest-only workflow can be
                // safely claimed after account authentication. Do not duplicate them.
                if ($quote->converted_order_id !== null) {
                    $order = Order::query()->whereKey($quote->converted_order_id)->lockForUpdate()->firstOrFail();
                    if ($order->user_id !== null && (int) $order->user_id !== (int) $customer->id) {
                        throw ValidationException::withMessages(['quote' => 'This order belongs to another customer account. Please contact sales.']);
                    }
                    if ($order->user_id === null) {
                        $order->forceFill(['user_id' => $customer->id])->save();
                    }
                    if ($quote->user_id === null) {
                        $quote->forceFill(['user_id' => $customer->id])->save();
                    }
                    return $quote;
                }
            }
            if ($decision === 'accept_only' && $quote->status === BulkQuoteRequest::STATUS_ACCEPTED) {
                return $quote;
            }
            $confirmedWithoutOrder = $quote->status === BulkQuoteRequest::STATUS_ACCEPTED && $quote->guest_decision === 'accept_only' && $decision === 'create_order';
            if ((! $confirmedWithoutOrder && ! in_array($quote->status, [BulkQuoteRequest::STATUS_QUOTATION_SENT, BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED], true)) ||
                $quote->guest_decision === 'changes_requested' || $quote->quotation_version < 1 || ! $quote->quotation_sent_at ||
                ($quote->quotation_valid_until && $quote->quotation_valid_until->lt(today()))) {
                throw ValidationException::withMessages(['quote' => 'This quotation is no longer available to confirm. Please contact our team.']);
            }
            $quote->forceFill(['status' => BulkQuoteRequest::STATUS_ACCEPTED, 'guest_decision' => $decision]);
            if ($decision === 'create_order') {
                $quote->user_id = $customer->id;
            }
            if ($decision === 'create_order') {
                $data = (array) $quote->quotation_details;
                $quantity = (int) ($data['quantity'] ?? 0);
                if ($quantity < 1 || $quote->quoted_amount === null || (float) $quote->quoted_amount <= 0) {
                    throw ValidationException::withMessages(['quote' => 'The quotation is missing the final item quantity or price. Please contact sales.']);
                }
                $subtotal = (float) ($data['subtotal'] ?? 0);
                $customization = (float) ($data['customization'] ?? 0);
                $shipping = (float) ($data['shipping'] ?? 0);
                $total = (float) $quote->quoted_amount;
                $order = Order::create([
                    'user_id' => $customer->id,
                    'order_number' => 'BQO-'.Str::upper(Str::random(16)),
                    'status' => 'quote_invoice_requested',
                    'payment_status' => 'pending',
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => $quote->quote_currency ?: 'USD',
                    'customer_name' => $quote->full_name,
                    'customer_email' => $quote->email,
                    'customer_phone' => $quote->phone,
                    'subtotal' => $subtotal,
                    'customization_total' => $customization,
                    'shipping_total' => $shipping,
                    'grand_total' => $total,
                    'total_quantity' => $quantity,
                    'information' => ['source' => 'guest_bulk_quote', 'bulk_quote_id' => $quote->id, 'reference' => $quote->reference, 'version' => $quote->quotation_version],
                    'shipping_address' => ['address' => ['address_line_1' => $quote->shipping_address, 'country' => $quote->country, 'state' => $quote->state_province, 'postal_code' => $quote->postal_code]],
                    'billing_address' => ['same_as_shipping' => true],
                    'payment_method' => ['method' => 'manual_invoice', 'payment_type' => 'invoice', 'requires_manual_review' => true],
                    'shipping_method' => ['name' => (string) ($data['shipping_method'] ?? $quote->preferredShippingMethodLabel())],
                    'idempotency_key' => 'guest-bulk-quote-'.$quote->id,
                    'placed_at' => now(),
                ]);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $quote->product_type,
                    'quantity' => $quantity,
                    'unit_price' => round($subtotal / $quantity, 2),
                    'customization_unit_price' => round($customization / $quantity, 2),
                    'line_total' => $subtotal + $customization,
                    'customization' => ['quote_reference' => $quote->reference, 'details' => $quote->artwork_details],
                ]);
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'quote_invoice_requested',
                    'title' => 'Bulk quotation accepted',
                    'description' => 'Customer accepted quotation '.$quote->reference.'. Awaiting invoice and payment instructions from sales.',
                    'occurred_at' => now(),
                ]);
                $quote->converted_order_id = $order->id;
            }
            $quote->save();
            $quote->messages()->create(['sender' => 'guest', 'kind' => 'decision', 'body' => $decision === 'create_order' ? 'Quotation accepted; create an order.' : 'Quotation accepted without creating an order.']);
            return $quote;
        });

        // Do not call the remote API inside the quotation transaction. Both
        // the storefront checkout and the quote-to-order path must deliver
        // orders through the SAME idempotent integration manager.
        if ($decision === 'create_order' && $result->converted_order_id) {
            $order = Order::query()->find($result->converted_order_id);
            if ($order && ($order->flowtrack_sync_status !== \App\Services\Integrations\FlowTrack\FlowTrackOrderSyncManager::STATUS_SYNCED
                || ! $wasLinkedToAccount)) {
                app(\App\Services\Integrations\FlowTrack\FlowTrackOrderSyncManager::class)->dispatch($order);
            }
        }

        return $result;
    }
}
