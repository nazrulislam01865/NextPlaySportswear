<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\OrderCreditNote;
use App\Models\OrderDownload;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function createPaymentAttempt(Order $order, array $payload): OrderPayment
    {
        return DB::transaction(function () use ($order, $payload): OrderPayment {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->canPay()) {
                throw ValidationException::withMessages(['payment_method' => 'This order is not currently eligible for payment.']);
            }

            $reference = 'attempt:'.hash('sha256', $locked->id.'|'.(string) $payload['idempotency_key']);
            $existing = $locked->payments()->where('provider_reference', $reference)->first();
            if ($existing instanceof OrderPayment) {
                return $existing;
            }

            $method = (string) $payload['payment_method'];
            $invoiceRequest = $method === 'invoice';
            $payment = $locked->payments()->create([
                'provider' => $method,
                'provider_reference' => $reference,
                'status' => 'pending',
                'amount' => $locked->outstandingAmount(),
                'currency' => $locked->currency,
                'attempted_at' => now(),
                'metadata' => [
                    'saved_payment_method_id' => $payload['saved_payment_method_id'] ?? null,
                    'integration_status' => $invoiceRequest ? 'invoice_review_required' : 'provider_handoff_required',
                ],
            ]);

            $locked->update([
                'payment_status' => $invoiceRequest ? 'pending' : 'processing',
                'status' => $invoiceRequest ? 'quote_invoice_requested' : 'payment_review',
            ]);

            $this->recordHistory(
                $locked,
                $locked->status,
                $invoiceRequest ? 'Invoice payment requested' : 'Payment submitted for secure processing',
                $invoiceRequest
                    ? 'The request is waiting for invoice approval and payment instructions.'
                    : 'The payment request is waiting for confirmation from the configured provider.',
            );

            return $payment;
        });
    }

    public function reorder(Order $order, array $payload): int
    {
        $selected = collect($payload['items'])->keyBy(fn (array $item): int => (int) $item['id']);
        $added = 0;

        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $selection = $selected->get($item->id);
            if (! is_array($selection) || empty($selection['selected']) || ! $item->product_slug) {
                continue;
            }

            $customization = (array) $item->customization;
            try {
                $this->cart->store([
                    'product_slug' => $item->product_slug,
                    'quantity' => (int) $selection['quantity'],
                    'design_option' => $customization['design_option'] ?? 'Repeat order configuration',
                    'delivery_preference' => $customization['delivery_preference'] ?? 'Standard production',
                    'size_summary' => $customization['size_summary'] ?? 'Review sizes before checkout',
                    'artwork_status' => $customization['artwork_status'] ?? 'Reuse prior artwork after review',
                    'notes' => trim(($customization['notes'] ?? '')."\nRepeat of order {$order->order_number}; verify current availability and proof."),
                    'configuration' => $customization['configuration'] ?? [],
                ]);
                $added++;
            } catch (\Throwable) {
                // Products removed from the live catalog are skipped rather than creating stale cart lines.
            }
        }

        if ($added === 0) {
            throw ValidationException::withMessages(['items' => 'None of the selected products are currently available for reorder.']);
        }

        return $added;
    }

    public function createCancellationRequest(Order $order, User $user, array $payload): OrderChangeRequest
    {
        return DB::transaction(function () use ($order, $user, $payload): OrderChangeRequest {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->canRequestCancellation()) {
                throw ValidationException::withMessages(['scope' => 'A cancellation request is not available for this order.']);
            }

            $requestedItems = $payload['scope'] === 'selected_items'
                ? $this->validatedOrderItemQuantities($locked, (array) ($payload['items'] ?? []), 'cancel')
                : [];
            if ($payload['scope'] === 'selected_items' && $requestedItems === []) {
                throw ValidationException::withMessages(['items' => 'Select at least one eligible item to cancel.']);
            }

            $request = $locked->changeRequests()->create([
                'user_id' => $user->id,
                'request_number' => $this->uniqueNumber('CCR'),
                'type' => 'cancel',
                'scope' => $payload['scope'],
                'status' => 'pending',
                'reason_code' => $payload['reason_code'],
                'reason' => $payload['reason'] ?? null,
                'requested_changes' => ['items' => $requestedItems],
            ]);

            $this->recordHistory($locked, 'cancellation_requested', 'Cancellation request received', 'Customer support will review the request before production or fulfillment progresses.', $user);

            return $request;
        });
    }

    public function createChangeRequest(Order $order, User $user, array $payload): OrderChangeRequest
    {
        return DB::transaction(function () use ($order, $user, $payload): OrderChangeRequest {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->canRequestChange()) {
                throw ValidationException::withMessages(['requested_changes' => 'Changes are no longer available for this order.']);
            }

            $validItemIds = $locked->items()->whereIn('id', array_map('intval', (array) ($payload['item_ids'] ?? [])))->pluck('id')->all();
            $request = $locked->changeRequests()->create([
                'user_id' => $user->id,
                'request_number' => $this->uniqueNumber('CHG'),
                'type' => 'change',
                'scope' => $validItemIds ? 'selected_items' : 'entire_order',
                'status' => 'pending',
                'reason_code' => $payload['reason_code'],
                'reason' => $payload['requested_changes'],
                'requested_changes' => ['item_ids' => $validItemIds, 'details' => $payload['requested_changes']],
            ]);

            $this->recordHistory($locked, 'change_requested', 'Order change requested', 'The requested changes are waiting for review. Production is not automatically changed until approval.', $user);

            return $request;
        });
    }

    public function createReturnRequest(Order $order, User $user, array $payload, string $type): OrderReturnRequest
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($order, $user, $payload, $type, &$storedPaths): OrderReturnRequest {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                $eligible = $type === 'exchange' ? $locked->canRequestExchange() : $locked->canRequestReturn();
                if (! $eligible) {
                    throw ValidationException::withMessages(['items' => 'This order is outside the eligible return or exchange window.']);
                }

                $items = $this->validatedOrderItemQuantities($locked, (array) $payload['items'], 'return');
                if ($items === []) {
                    throw ValidationException::withMessages(['items' => 'Select at least one eligible delivered item.']);
                }

                $returnRequest = $locked->returnRequests()->create([
                    'user_id' => $user->id,
                    'return_number' => $this->uniqueNumber($type === 'exchange' ? 'EXC' : 'RTN'),
                    'type' => $type,
                    'status' => 'requested',
                    'reason_code' => $payload['reason_code'],
                    'reason' => $payload['reason'] ?? null,
                    'requested_resolution' => $type === 'exchange' ? 'exchange' : $payload['requested_resolution'],
                    'exchange_notes' => $payload['exchange_notes'] ?? null,
                    'requested_at' => now(),
                ]);

                $sourceItems = collect($payload['items'])->keyBy(fn (array $item): int => (int) $item['id']);
                foreach ($items as $itemData) {
                    $source = (array) $sourceItems->get($itemData['id'], []);
                    $returnRequest->items()->create([
                        'order_item_id' => $itemData['id'],
                        'quantity' => $itemData['quantity'],
                        'item_condition' => $source['condition'] ?? null,
                        'customer_note' => $source['note'] ?? null,
                        'exchange_configuration' => $type === 'exchange' ? ['replacement' => $source['replacement'] ?? null] : null,
                    ]);
                }

                foreach ((array) ($payload['evidence'] ?? []) as $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid()) {
                        continue;
                    }

                    $path = $file->store('return-evidence/'.$returnRequest->id, 'local');
                    if (! $path) {
                        throw ValidationException::withMessages([
                            'evidence' => 'One of the evidence files could not be stored securely.',
                        ]);
                    }

                    $storedPaths[] = $path;
                    $returnRequest->attachments()->create([
                        'uploaded_by' => $user->id,
                        'file_path' => $path,
                        'original_name' => $this->safeOriginalName($file),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }

                $this->recordHistory($locked, $type.'_requested', str($type)->headline().' request received', 'The request is awaiting eligibility review.', $user, ['return_number' => $returnRequest->return_number]);

                return $returnRequest;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }
    }

    public function updateOrder(Order $order, User $admin, array $payload): Order
    {
        return DB::transaction(function () use ($order, $admin, $payload): Order {
            $locked = Order::query()->lockForUpdate()->with('items.shipmentItems')->findOrFail($order->id);
            $oldStatus = $locked->status;
            $oldPayment = $locked->payment_status;
            $oldFulfillment = $locked->fulfillment_status;
            $amountDue = $locked->outstandingAmount();

            if (in_array($oldStatus, ['completed', 'cancelled'], true) && $payload['status'] !== $oldStatus) {
                throw ValidationException::withMessages([
                    'status' => 'A completed or cancelled order cannot be reopened from this screen.',
                ]);
            }

            $issuedRefundTotal = (float) $locked->refunds()->where('status', 'issued')->sum('amount');
            if ($issuedRefundTotal > 0) {
                $expectedPaymentStatus = $issuedRefundTotal >= (float) $locked->grand_total
                    ? 'refunded'
                    : 'partially_refunded';

                if ($payload['payment_status'] !== $expectedPaymentStatus) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Payment status is controlled by issued refunds for this order.',
                    ]);
                }
            }

            $timestamps = [];
            if ($payload['payment_status'] === 'paid' && ! $locked->paid_at) {
                $timestamps['paid_at'] = now();
            }
            if ($payload['status'] === 'cancelled' && ! $locked->cancelled_at) {
                $timestamps['cancelled_at'] = now();
            }
            if ($payload['status'] === 'delivered' && ! $locked->delivered_at) {
                $timestamps['delivered_at'] = now();
            }
            if ($payload['status'] === 'completed' && ! $locked->completed_at) {
                $timestamps['completed_at'] = now();
            }

            $locked->update(array_merge(
                Arr::only($payload, ['status', 'payment_status', 'fulfillment_status', 'admin_note']),
                $timestamps,
            ));

            if ($payload['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                if ($locked->items->contains(fn (OrderItem $item): bool => $item->shipmentItems->isNotEmpty())) {
                    throw ValidationException::withMessages([
                        'status' => 'An order with an allocated shipment cannot be cancelled directly. Resolve the shipment or return workflow instead.',
                    ]);
                }

                foreach ($locked->items as $item) {
                    $remaining = $item->remainingFulfillableQuantity();
                    if ($remaining > 0) {
                        $item->increment('cancelled_quantity', $remaining);
                    }
                }
            }

            if (
                in_array($payload['status'], ['delivered', 'completed'], true)
                || $payload['fulfillment_status'] === 'fulfilled'
            ) {
                foreach ($locked->items as $item) {
                    $deliveredQuantity = max(0, $item->quantity - $item->cancelled_quantity);
                    if ($item->fulfilled_quantity < $deliveredQuantity) {
                        $item->update(['fulfilled_quantity' => $deliveredQuantity]);
                    }
                }
            }

            if ($payload['payment_status'] === 'paid' && $oldPayment !== 'paid' && $amountDue > 0) {
                $locked->payments()->create([
                    'provider' => 'admin',
                    'status' => 'paid',
                    'amount' => $amountDue,
                    'currency' => $locked->currency,
                    'attempted_at' => now(),
                    'paid_at' => now(),
                    'metadata' => ['recorded_by' => $admin->id],
                ]);
            }

            if ($locked->shipments()->exists() && ! in_array($locked->status, ['cancelled', 'completed'], true)) {
                $this->syncOrderFulfillmentFromShipments($locked);
                $locked->refresh();
            }

            if (
                $oldStatus !== $locked->status
                || $oldPayment !== $locked->payment_status
                || $oldFulfillment !== $locked->fulfillment_status
            ) {
                $this->recordHistory(
                    $locked,
                    $locked->status,
                    $locked->statusLabel(),
                    'Order status updated by an administrator.',
                    $admin,
                    [
                        'payment_status' => $locked->payment_status,
                        'fulfillment_status' => $locked->fulfillment_status,
                    ],
                );
            }

            return $locked->fresh(['items', 'payments']);
        });
    }

    public function createShipment(Order $order, User $admin, array $payload): OrderShipment
    {
        return DB::transaction(function () use ($order, $admin, $payload): OrderShipment {
            $locked = Order::query()->lockForUpdate()->with('items.shipmentItems')->findOrFail($order->id);
            $items = $this->validatedOrderItemQuantities($locked, $payload['items'], 'fulfill');
            if ($items === []) {
                throw ValidationException::withMessages(['items' => 'Select at least one unallocated item.']);
            }

            $status = $payload['status'];
            $shipment = $locked->shipments()->create([
                'shipment_number' => $this->uniqueNumber('SHP'),
                'status' => $status,
                'carrier' => $payload['carrier'] ?? null,
                'service' => $payload['service'] ?? null,
                'tracking_number' => $payload['tracking_number'] ?? null,
                'tracking_url' => $payload['tracking_url'] ?? null,
                'shipping_address' => $locked->shipping_address,
                'notes' => $payload['notes'] ?? null,
                'shipped_at' => in_array($status, ['in_transit', 'out_for_delivery', 'delivered'], true) ? now() : null,
                'estimated_delivery_at' => $payload['estimated_delivery_at'] ?? null,
                'delivered_at' => $status === 'delivered' ? now() : null,
            ]);

            foreach ($items as $itemData) {
                $shipment->items()->create([
                    'order_item_id' => $itemData['id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }

            $this->syncOrderFulfillmentFromShipments($locked);
            $locked->refresh();

            $this->recordHistory(
                $locked,
                $locked->status,
                $shipment->statusLabel(),
                'Shipment '.$shipment->shipment_number.' was created.',
                $admin,
                ['shipment_number' => $shipment->shipment_number],
            );

            return $shipment->load('items.orderItem');
        });
    }

    public function updateShipment(OrderShipment $shipment, User $admin, array $payload): OrderShipment
    {
        return DB::transaction(function () use ($shipment, $admin, $payload): OrderShipment {
            $locked = OrderShipment::query()
                ->lockForUpdate()
                ->with('order')
                ->findOrFail($shipment->id);

            $oldStatus = $locked->status;
            $status = $payload['status'];
            $allowedStatuses = config('commerce.shipment_status_transitions.'.$oldStatus, [$oldStatus]);
            if (! in_array($status, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => 'This shipment cannot move from '.str($oldStatus)->headline().' to '.str($status)->headline().'.',
                ]);
            }

            $updates = Arr::only($payload, [
                'status',
                'carrier',
                'service',
                'tracking_number',
                'tracking_url',
                'estimated_delivery_at',
                'notes',
            ]);

            if (in_array($status, ['in_transit', 'out_for_delivery', 'delivered'], true) && ! $locked->shipped_at) {
                $updates['shipped_at'] = now();
            }
            if ($status === 'delivered' && ! $locked->delivered_at) {
                $updates['delivered_at'] = now();
            }

            $locked->update($updates);
            $this->syncOrderFulfillmentFromShipments($locked->order);
            $locked->order->refresh();

            if ($oldStatus !== $locked->status) {
                $this->recordHistory(
                    $locked->order,
                    $locked->order->status,
                    $locked->statusLabel(),
                    'Shipment '.$locked->shipment_number.' status was updated.',
                    $admin,
                    ['shipment_number' => $locked->shipment_number],
                );
            }

            return $locked->fresh(['items.orderItem', 'order']);
        });
    }

    public function resolveChangeRequest(OrderChangeRequest $request, User $admin, array $payload): OrderChangeRequest
    {
        return DB::transaction(function () use ($request, $admin, $payload): OrderChangeRequest {
            $locked = OrderChangeRequest::query()
                ->lockForUpdate()
                ->with('order.items.shipmentItems')
                ->findOrFail($request->id);

            $oldStatus = $locked->status;
            if (in_array($oldStatus, ['completed', 'rejected'], true) && $payload['status'] !== $oldStatus) {
                throw ValidationException::withMessages([
                    'status' => 'A completed or rejected request cannot be reopened.',
                ]);
            }

            if ($locked->type === 'cancel' && $payload['status'] === 'completed' && $oldStatus !== 'completed') {
                $requested = collect(data_get($locked->requested_changes, 'items', []))
                    ->keyBy(fn (array $item): int => (int) ($item['id'] ?? 0));

                foreach ($locked->order->items as $item) {
                    $available = $item->remainingFulfillableQuantity();
                    if ($available < 1) {
                        continue;
                    }

                    $quantity = $locked->scope === 'entire_order'
                        ? $available
                        : min($available, (int) data_get($requested->get($item->id), 'quantity', 0));

                    if ($quantity > 0) {
                        $item->increment('cancelled_quantity', $quantity);
                    }
                }

                $locked->order->refresh()->load('items');
                $allCancelled = $locked->order->items->every(
                    fn (OrderItem $item): bool => $item->cancelled_quantity >= $item->quantity,
                );

                if ($allCancelled) {
                    $locked->order->update([
                        'status' => 'cancelled',
                        'fulfillment_status' => 'unfulfilled',
                        'cancelled_at' => $locked->order->cancelled_at ?? now(),
                    ]);
                } else {
                    $this->syncOrderFulfillmentFromShipments($locked->order);
                }
            }

            $terminal = in_array($payload['status'], ['completed', 'rejected'], true);
            $locked->update([
                'status' => $payload['status'],
                'admin_note' => $payload['admin_note'] ?? null,
                'resolved_by' => $terminal ? $admin->id : null,
                'resolved_at' => $terminal ? now() : null,
            ]);

            if ($oldStatus !== $locked->status) {
                $this->recordHistory(
                    $locked->order,
                    $locked->type.'_request_'.$locked->status,
                    str($locked->type)->headline().' request '.str($locked->status)->headline(),
                    $locked->admin_note,
                    $admin,
                    ['request_number' => $locked->request_number],
                );
            }

            return $locked->fresh(['order.items', 'user', 'resolver']);
        });
    }

    public function updateReturn(OrderReturnRequest $request, User $admin, array $payload): OrderReturnRequest
    {
        return DB::transaction(function () use ($request, $admin, $payload): OrderReturnRequest {
            $locked = OrderReturnRequest::query()->lockForUpdate()->with(['order','items.orderItem','refunds'])->findOrFail($request->id);
            $oldStatus = $locked->status;
            $allowedStatuses = config('commerce.return_status_transitions.'.$oldStatus, [$oldStatus]);
            if (! in_array($payload['status'], $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => 'This request cannot move from '.str($oldStatus)->headline().' to '.str($payload['status'])->headline().'.',
                ]);
            }

            $approvedAmount = (float) ($payload['approved_amount'] ?? $locked->approved_amount ?? 0);
            if ($approvedAmount > (float) $locked->order->grand_total) {
                throw ValidationException::withMessages([
                    'approved_amount' => 'The approved amount cannot exceed the order total.',
                ]);
            }

            $timestamps = [];
            if ($payload['status'] === 'received' && ! $locked->received_at) $timestamps['received_at'] = now();
            if ($payload['status'] === 'completed' && ! $locked->completed_at) $timestamps['completed_at'] = now();
            if (in_array($payload['status'], ['completed','rejected','cancelled'], true)) $timestamps['resolved_at'] = now();

            $locked->update(array_merge([
                'status' => $payload['status'],
                'admin_note' => $payload['admin_note'] ?? null,
                'approved_amount' => $payload['approved_amount'] ?? $locked->approved_amount,
                'resolved_by' => in_array($payload['status'], ['completed','rejected','cancelled'], true) ? $admin->id : $locked->resolved_by,
            ], $timestamps));

            if (in_array($payload['status'], ['received', 'completed'], true) && ! in_array($oldStatus, ['received', 'completed'], true)) {
                foreach ($locked->items as $returnItem) {
                    $returnItem->orderItem()->increment('returned_quantity', $returnItem->quantity);
                }

                $locked->order->load('items');
                $deliveredQuantity = (int) $locked->order->items->sum('fulfilled_quantity');
                $returnedQuantity = (int) $locked->order->items->sum('returned_quantity');
                if ($deliveredQuantity > 0 && $returnedQuantity >= $deliveredQuantity) {
                    $locked->order->update(['fulfillment_status' => 'returned']);
                }
            }

            $refundStatus = $payload['refund_status'] ?? null;
            if ($locked->type === 'return' && $approvedAmount > 0 && $refundStatus) {
                $refund = $locked->refunds()->first();

                if ($refundStatus === 'issued') {
                    $paidTotal = (float) $locked->order->payments()->where('status', 'paid')->sum('amount');
                    $otherIssued = (float) $locked->order->refunds()
                        ->where('status', 'issued')
                        ->when($refund, fn ($query) => $query->where('id', '!=', $refund->id))
                        ->sum('amount');

                    if (round($otherIssued + $approvedAmount, 2) > round($paidTotal, 2)) {
                        throw ValidationException::withMessages([
                            'refund_status' => 'The issued refund total cannot exceed confirmed paid payments for this order.',
                        ]);
                    }
                }
                if ($refund?->status === 'issued' && $refundStatus !== 'issued') {
                    throw ValidationException::withMessages([
                        'refund_status' => 'An issued refund cannot be moved back to another status.',
                    ]);
                }

                if (! $refund) {
                    $refund = $locked->refunds()->create([
                        'order_id' => $locked->order_id,
                        'refund_number' => $this->uniqueNumber('RFN'),
                        'amount' => $approvedAmount,
                        'currency' => $locked->order->currency,
                        'method' => $locked->requested_resolution === 'store_credit' ? 'store_credit' : 'original_payment',
                        'status' => $refundStatus,
                        'provider_reference' => $payload['provider_reference'] ?? null,
                        'reason' => $locked->reason,
                        'processed_at' => $refundStatus === 'issued' ? now() : null,
                    ]);
                } else {
                    $refund->update([
                        'amount' => $approvedAmount,
                        'status' => $refundStatus,
                        'provider_reference' => $payload['provider_reference'] ?? $refund->provider_reference,
                        'processed_at' => $refundStatus === 'issued' ? now() : $refund->processed_at,
                    ]);
                }

                if ($refundStatus === 'issued' && ! $refund->creditNote) {
                    $refund->creditNote()->create([
                        'order_id' => $locked->order_id,
                        'credit_note_number' => $this->uniqueNumber('CN'),
                        'amount' => $refund->amount,
                        'currency' => $refund->currency,
                        'reason' => $refund->reason,
                        'issued_at' => now(),
                    ]);
                }

                $issuedTotal = (float) $locked->order->refunds()->where('status', 'issued')->sum('amount');
                $locked->order->update(['payment_status' => $issuedTotal >= (float) $locked->order->grand_total ? 'refunded' : 'partially_refunded']);
            }

            $this->recordHistory($locked->order, 'return_'.$locked->status, $locked->statusLabel(), 'Return or exchange '.$locked->return_number.' was updated.', $admin);

            return $locked->fresh(['order','items.orderItem','refunds.creditNote']);
        });
    }

    public function storeDownload(Order $order, User $admin, array $payload, UploadedFile $file): OrderDownload
    {
        $itemId = isset($payload['order_item_id']) && $order->items()->whereKey($payload['order_item_id'])->exists()
            ? (int) $payload['order_item_id'] : null;
        $path = $file->store('order-downloads/'.$order->id, 'local');

        if (! $path) {
            throw ValidationException::withMessages(['file' => 'The private file could not be stored.']);
        }

        try {
            return $order->downloads()->create([
                'order_item_id' => $itemId,
                'token' => Str::random(48),
                'title' => $payload['title'],
                'file_path' => $path,
                'original_name' => $this->safeOriginalName($file),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'download_limit' => $payload['download_limit'] ?? null,
                'expires_at' => $payload['expires_at'] ?? null,
                'is_active' => true,
                'license_note' => $payload['license_note'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function deleteDownload(OrderDownload $download): void
    {
        Storage::disk('local')->delete($download->file_path);
        $download->delete();
    }

    public function recordHistory(Order $order, string $status, string $title, ?string $description = null, ?User $actor = null, array $metadata = []): void
    {
        $order->histories()->create([
            'actor_id' => $actor?->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function validatedOrderItemQuantities(Order $order, array $requested, string $purpose): array
    {
        $requested = collect($requested)->keyBy(fn (array $item): int => (int) ($item['id'] ?? 0));
        $result = [];
        $items = $order->relationLoaded('items')
            ? $order->items
            : ($purpose === 'return'
                ? $order->items()->with('returnItems.returnRequest')->get()
                : $order->items()->get());

        foreach ($items as $item) {
            $entry = $requested->get($item->id);
            if (! is_array($entry)) continue;
            $available = match ($purpose) {
                'return' => $item->returnableQuantity(),
                'fulfill' => $item->remainingFulfillableQuantity(),
                default => $item->remainingCancellableQuantity(),
            };
            $quantity = max(0, (int) ($entry['quantity'] ?? 0));
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'items' => 'A selected quantity is no longer available. Refresh the page and try again.',
                ]);
            }

            if ($quantity > 0) {
                $result[] = ['id' => $item->id, 'quantity' => $quantity];
            }
        }

        return $result;
    }

    private function syncOrderFulfillmentFromShipments(Order $order): void
    {
        $order->load(['items', 'shipments.items']);

        $allocated = 0;
        $shipped = 0;
        $delivered = 0;
        $deliveredByItem = [];

        foreach ($order->shipments as $shipment) {
            foreach ($shipment->items as $shipmentItem) {
                $quantity = (int) $shipmentItem->quantity;
                $allocated += $quantity;

                if (in_array($shipment->status, ['in_transit', 'out_for_delivery', 'delivered'], true)) {
                    $shipped += $quantity;
                }

                if ($shipment->status === 'delivered') {
                    $delivered += $quantity;
                    $deliveredByItem[$shipmentItem->order_item_id] =
                        ($deliveredByItem[$shipmentItem->order_item_id] ?? 0) + $quantity;
                }
            }
        }

        foreach ($order->items as $item) {
            $deliverable = max(0, $item->quantity - $item->cancelled_quantity);
            $deliveredQuantity = min($deliverable, (int) ($deliveredByItem[$item->id] ?? 0));
            if ($item->fulfilled_quantity !== $deliveredQuantity) {
                $item->update(['fulfilled_quantity' => $deliveredQuantity]);
            }
        }

        $fulfillable = (int) $order->items->sum(
            fn (OrderItem $item): int => max(0, $item->quantity - $item->cancelled_quantity),
        );

        $fulfillmentStatus = match (true) {
            $fulfillable > 0 && $shipped >= $fulfillable => 'fulfilled',
            $allocated > 0 => 'partial',
            default => 'unfulfilled',
        };

        $orderStatus = $order->status;
        if (! in_array($orderStatus, ['cancelled', 'completed'], true)) {
            $shipmentDrivenStatus = match (true) {
                $fulfillable > 0 && $delivered >= $fulfillable => 'delivered',
                $fulfillable > 0 && $shipped >= $fulfillable => 'shipped',
                $shipped > 0 => 'partially_shipped',
                default => null,
            };

            if ($shipmentDrivenStatus !== null) {
                $orderStatus = $shipmentDrivenStatus;
            } elseif (in_array($orderStatus, ['partially_shipped', 'shipped', 'delivered'], true)) {
                $orderStatus = 'in_production';
            }
        }

        $updates = [
            'status' => $orderStatus,
            'fulfillment_status' => $fulfillmentStatus,
        ];

        if ($fulfillable > 0 && $delivered >= $fulfillable) {
            $updates['delivered_at'] = $order->delivered_at ?? now();
        } elseif ($order->status !== 'completed') {
            $updates['delivered_at'] = null;
        }

        $order->update($updates);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename($file->getClientOriginalName());
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name) ?: 'download';

        return Str::substr($name, 0, 240);
    }

    private function uniqueNumber(string $prefix): string
    {
        do {
            $number = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (
            OrderChangeRequest::where('request_number', $number)->exists()
            || OrderReturnRequest::where('return_number', $number)->exists()
            || OrderShipment::where('shipment_number', $number)->exists()
            || OrderRefund::where('refund_number', $number)->exists()
            || OrderCreditNote::where('credit_note_number', $number)->exists()
        );

        return $number;
    }
}
