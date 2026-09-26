<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\OrderReturnRequest;
use App\Models\PaymentMethod;
use App\Models\PaymentWebhookEvent;
use App\Payments\DTO\GatewayPaymentStatus;
use App\Payments\DTO\GatewayRedirect;
use App\Payments\DTO\GatewayRefundResult;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\Money;
use App\Payments\PaymentGatewayManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PaymentOrchestrator
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    public function initiateCheckout(Order $order, string $idempotencySeed): GatewayRedirect
    {
        $snapshot = (array) $order->payment_method;
        $methodCode = (string) ($snapshot['code'] ?? $snapshot['method'] ?? '');

        return $this->initiate($order, $methodCode, $idempotencySeed, $snapshot);
    }

    public function initiateOrderPayment(Order $order, string $methodCode, string $idempotencySeed): GatewayRedirect
    {
        return $this->initiate($order, $methodCode, $idempotencySeed);
    }

    public function processWebhook(PaymentWebhookEvent $webhook): void
    {
        $supportedEvents = [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
            'refund.created',
            'refund.updated',
            'refund.failed',
        ];

        // Stripe can be configured to send extra event types. A valid signed
        // but irrelevant event should be acknowledged, not retried as an error.
        if (! in_array($webhook->event_type, $supportedEvents, true)) {
            return;
        }

        $payload = (array) $webhook->payload;
        $object = (array) data_get($payload, 'data.object', []);
        $payment = $this->findWebhookPayment($webhook->provider, $object);

        if (! $payment instanceof OrderPayment) {
            throw new PaymentGatewayException('Webhook could not be matched to an internal payment attempt.');
        }

        $webhook->forceFill(['order_payment_id' => $payment->id])->save();

        match ($webhook->event_type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->handleProviderSuccess($payment, $object),
            'checkout.session.async_payment_failed' => $this->markFailed($payment, 'provider_failed', 'Stripe reported that the payment failed.'),
            'checkout.session.expired' => $this->markExpired($payment),
            'refund.created', 'refund.updated', 'refund.failed' => $this->handleProviderRefund($payment, $object),
            default => null,
        };
    }

    public function issueReturnRefund(OrderReturnRequest $returnRequest, float $amount): OrderRefund
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['approved_amount' => 'Refund amount must be greater than zero.']);
        }

        $refund = DB::transaction(function () use ($returnRequest, $amount): OrderRefund {
            $lockedReturn = OrderReturnRequest::query()
                ->lockForUpdate()
                ->with(['order.payments', 'refunds'])
                ->findOrFail($returnRequest->id);

            if ($lockedReturn->type !== 'return') {
                throw ValidationException::withMessages(['refund_status' => 'Only return requests can create a payment-provider refund.']);
            }

            if ($lockedReturn->requested_resolution === 'store_credit') {
                throw ValidationException::withMessages(['refund_status' => 'Store credit is issued internally and must not call the card payment provider.']);
            }

            $existing = $lockedReturn->refunds()->lockForUpdate()->first();
            if ($existing?->status === 'issued') {
                if (round((float) $existing->amount, 2) !== round($amount, 2)) {
                    throw ValidationException::withMessages(['approved_amount' => 'An issued refund amount cannot be changed.']);
                }

                return $existing->fresh(['payment']);
            }

            $paidTotal = (float) $lockedReturn->order->payments()->where('status', 'paid')->sum('amount');
            $otherIssued = (float) $lockedReturn->order->refunds()
                ->where('status', 'issued')
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->sum('amount');

            if (round($otherIssued + $amount, 2) > round($paidTotal, 2)) {
                throw ValidationException::withMessages([
                    'refund_status' => 'The refund total cannot exceed confirmed provider payments for this order.',
                ]);
            }

            $payments = $lockedReturn->order->payments()
                ->where('status', 'paid')
                ->whereNotIn('provider', ['manual', 'invoice'])
                ->lockForUpdate()
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->get();

            $payment = $payments->first(function (OrderPayment $candidate) use ($amount): bool {
                $remaining = round((float) $candidate->amount - (float) $candidate->refunded_amount, 2);

                return $remaining >= round($amount, 2) && filled($candidate->provider_payment_id);
            });

            if (! $payment instanceof OrderPayment) {
                throw ValidationException::withMessages([
                    'refund_status' => 'No single confirmed provider payment has enough refundable balance. Review duplicate/partial payments before refunding.',
                ]);
            }

            $attributes = [
                'order_id' => $lockedReturn->order_id,
                'order_payment_id' => $payment->id,
                'amount' => $amount,
                'currency' => $lockedReturn->order->currency,
                'method' => 'original_payment',
                'status' => 'processing',
                'reason' => $lockedReturn->reason,
                'processed_at' => null,
            ];

            if (! $existing) {
                $existing = $lockedReturn->refunds()->create(array_merge($attributes, [
                    'refund_number' => $this->newRefundNumber(),
                ]));
            } else {
                $existing->update($attributes);
            }

            return $existing->fresh(['payment']);
        });

        if ($refund->status === 'issued') {
            return $refund;
        }

        $payment = $refund->payment;
        if (! $payment instanceof OrderPayment) {
            throw new PaymentGatewayException('The refund is not linked to a confirmed payment attempt.');
        }

        $gateway = $this->gateways->gateway((string) ($payment->gateway ?: $payment->provider));
        if (! $gateway->configured()) {
            throw new PaymentGatewayException(ucfirst((string) $payment->provider).' is not fully configured for secure refunds.');
        }

        try {
            $result = $gateway->refund(
                $payment,
                (float) $refund->amount,
                (string) $refund->reason,
                'refund:'.$refund->refund_number,
                [
                    'order_refund_id' => $refund->id,
                    'refund_number' => $refund->refund_number,
                ],
            );
        } catch (Throwable $exception) {
            $refund->forceFill(['status' => 'failed'])->save();
            throw $exception;
        }

        $this->applyRefundResult($refund, $payment, $result);
        $refund = $refund->fresh(['payment', 'creditNote']);

        if ($refund->status === 'failed') {
            throw new PaymentGatewayException('The payment provider did not complete the refund. Review the provider dashboard and retry after resolving the failure.');
        }

        return $refund;
    }

    public function reconcile(OrderPayment $payment): void
    {
        if (! in_array($payment->status, ['pending', 'processing'], true)) {
            return;
        }

        $gateway = $this->gateways->gateway((string) ($payment->gateway ?: $payment->provider));
        $status = $gateway->retrievePayment($payment);

        match ($status->status) {
            'paid' => $this->applyReconciledSuccess($payment, $status),
            'failed' => $this->markFailed($payment, 'reconciled_failed', 'The payment provider reports that this payment failed.'),
            'expired' => $this->markExpired($payment),
            default => null,
        };
    }

    private function initiate(Order $order, string $methodCode, string $idempotencySeed, array $snapshot = []): GatewayRedirect
    {
        if ($methodCode === 'saved_card') {
            throw ValidationException::withMessages([
                'payment_method' => 'Saved cards are temporarily disabled until Stripe-hosted vaulting is enabled. Choose Credit / Debit Card instead.',
            ]);
        }

        $method = PaymentMethod::query()->where('code', $methodCode)->where('is_active', true)->first();

        if (! $method instanceof PaymentMethod) {
            throw ValidationException::withMessages(['payment_method' => 'The selected payment method is not currently available.']);
        }

        if ($method->minimum_total !== null && (float) $order->grand_total < (float) $method->minimum_total) {
            throw ValidationException::withMessages(['payment_method' => 'The selected payment method is not available for this order total.']);
        }
        if ($method->maximum_total !== null && (float) $order->grand_total > (float) $method->maximum_total) {
            throw ValidationException::withMessages(['payment_method' => 'The selected payment method is not available for this order total.']);
        }

        $provider = (string) $method->provider;
        $gateway = $this->gateways->gateway($provider);

        if (! $gateway->configured()) {
            throw ValidationException::withMessages([
                'payment_method' => ucfirst($provider).' is not configured on this server yet.',
            ]);
        }

        $attemptKey = hash('sha256', 'nextplay|'.$order->id.'|'.$method->id.'|'.$idempotencySeed);
        $payment = $this->createAttempt($order, $method, $attemptKey, $snapshot);

        if ($payment->status === 'paid') {
            return new GatewayRedirect(null, $payment->provider_reference, $payment->provider_session_id, $payment->provider_payment_id);
        }

        $existingUrl = data_get($payment->metadata, 'checkout_url');
        if ($payment->status === 'processing' && is_string($existingUrl) && $existingUrl !== '') {
            return new GatewayRedirect($existingUrl, $payment->provider_reference, $payment->provider_session_id, $payment->provider_payment_id);
        }

        if ($provider === 'manual') {
            return $gateway->createCheckout($order, $payment);
        }

        try {
            $redirect = $gateway->createCheckout($order->fresh(), $payment->fresh());

            DB::transaction(function () use ($order, $payment, $redirect): void {
                $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
                $metadata = array_merge((array) $lockedPayment->metadata, $redirect->metadata);
                if ($redirect->url) {
                    $metadata['checkout_url'] = $redirect->url;
                }

                $lockedPayment->update([
                    'provider_reference' => $redirect->providerReference,
                    'provider_session_id' => $redirect->providerSessionId,
                    'provider_payment_id' => $redirect->providerPaymentId,
                    'status' => 'processing',
                    'expires_at' => isset($redirect->metadata['expires_at']) ? date('Y-m-d H:i:s', (int) $redirect->metadata['expires_at']) : null,
                    'metadata' => $metadata,
                ]);

                Order::query()->whereKey($order->id)->where('payment_status', '!=', 'paid')->update([
                    'payment_status' => 'processing',
                    'status' => 'pending_payment',
                ]);
            });

            return $redirect;
        } catch (Throwable $exception) {
            // A network timeout does not prove Stripe failed to create the
            // Session. Keep this attempt resumable and reuse the SAME gateway
            // idempotency key on the next try to prevent double charges.
            $this->markStartUncertain($payment);
            throw $exception;
        }
    }

    private function createAttempt(Order $order, PaymentMethod $method, string $attemptKey, array $snapshot): OrderPayment
    {
        try {
            return DB::transaction(function () use ($order, $method, $attemptKey, $snapshot): OrderPayment {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

                if ($locked->payment_status === 'paid') {
                    $paid = $locked->payments()->where('status', 'paid')->latest()->first();
                    if ($paid instanceof OrderPayment) {
                        return $paid;
                    }
                }

                if (! $locked->canPay() && $locked->payment_status !== 'processing') {
                    throw ValidationException::withMessages(['payment_method' => 'This order is not currently eligible for payment.']);
                }

                $existing = $locked->payments()->where('idempotency_key', $attemptKey)->first();
                if ($existing instanceof OrderPayment) {
                    return $existing;
                }

                // Never create a second live provider payment while another
                // attempt for the same method may still complete remotely.
                $inFlight = $locked->payments()
                    ->where('payment_method_id', $method->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->latest('id')
                    ->first();

                if ($inFlight instanceof OrderPayment) {
                    $stillUsable = $inFlight->expires_at?->isFuture()
                        ?? $inFlight->attempted_at?->greaterThan(now()->subMinutes(30))
                        ?? true;

                    if ($stillUsable) {
                        return $inFlight;
                    }
                }

                $capabilities = $this->gateways->capabilities((string) $method->provider);
                $manual = (bool) $capabilities['requires_manual_review'];
                $payment = $locked->payments()->create([
                    'payment_method_id' => $method->id,
                    'gateway' => $method->provider,
                    'provider' => $method->provider,
                    'idempotency_key' => $attemptKey,
                    'status' => 'pending',
                    'amount' => $locked->outstandingAmount(),
                    'currency' => $locked->currency,
                    'attempted_at' => now(),
                    'metadata' => [
                        'method_code' => $method->code,
                        'integration_status' => $manual ? 'manual_review_required' : 'provider_handoff_required',
                    ],
                ]);

                if ($snapshot === []) {
                    $locked->payment_method = [
                        'id' => $method->id,
                        'code' => $method->code,
                        'method' => $method->code,
                        'label' => $method->name,
                        'provider' => $method->provider,
                        'payment_type' => $method->payment_type,
                        'requires_provider_redirect' => (bool) $capabilities['requires_provider_redirect'],
                        'requires_manual_review' => (bool) $capabilities['requires_manual_review'],
                    ];
                }

                $locked->payment_status = $manual ? 'pending' : 'processing';
                $locked->status = $manual ? 'quote_invoice_requested' : 'pending_payment';
                $locked->save();

                $locked->histories()->create([
                    'status' => $locked->status,
                    'title' => $manual ? 'Manual payment requested' : 'Secure payment started',
                    'description' => $manual
                        ? 'The payment request is waiting for administrator review.'
                        : 'A provider-hosted payment session is being created. The order will become paid only after verified provider confirmation.',
                    'occurred_at' => now(),
                ]);

                return $payment;
            });
        } catch (QueryException $exception) {
            $existing = OrderPayment::query()->where('idempotency_key', $attemptKey)->first();
            if ($existing instanceof OrderPayment) {
                return $existing;
            }
            throw $exception;
        }
    }

    private function findWebhookPayment(string $provider, array $object): ?OrderPayment
    {
        $paymentId = (int) (data_get($object, 'metadata.payment_id') ?: data_get($object, 'metadata.order_payment_id', 0));
        if ($paymentId > 0) {
            return OrderPayment::query()->whereKey($paymentId)->where('provider', $provider)->first();
        }

        $paymentIntent = $object['payment_intent'] ?? null;
        if (is_string($paymentIntent) && $paymentIntent !== '') {
            $payment = OrderPayment::query()
                ->where('provider', $provider)
                ->where('provider_payment_id', $paymentIntent)
                ->first();

            if ($payment instanceof OrderPayment) {
                return $payment;
            }
        }

        $sessionId = (string) ($object['id'] ?? '');

        return $sessionId === ''
            ? null
            : OrderPayment::query()->where('provider', $provider)->where('provider_session_id', $sessionId)->first();
    }

    private function handleProviderRefund(OrderPayment $payment, array $object): void
    {
        $refundId = (int) data_get($object, 'metadata.order_refund_id', 0);
        $providerReference = (string) ($object['id'] ?? '');

        $refund = $refundId > 0
            ? OrderRefund::query()->whereKey($refundId)->where('order_payment_id', $payment->id)->first()
            : null;

        if (! $refund instanceof OrderRefund && $providerReference !== '') {
            $refund = OrderRefund::query()
                ->where('order_payment_id', $payment->id)
                ->where('provider_reference', $providerReference)
                ->first();
        }

        if (! $refund instanceof OrderRefund) {
            throw new PaymentGatewayException('Refund webhook could not be matched to an internal refund.');
        }

        $status = (string) ($object['status'] ?? 'pending');
        $result = new GatewayRefundResult(
            status: $status,
            providerReference: $providerReference ?: $refund->provider_reference,
            metadata: [
                'failure_reason' => $object['failure_reason'] ?? null,
                'pending_reason' => $object['pending_reason'] ?? null,
            ],
        );

        $this->applyRefundResult($refund, $payment, $result);
    }

    private function applyRefundResult(OrderRefund $refund, OrderPayment $payment, GatewayRefundResult $result): void
    {
        $status = strtolower($result->status);

        if ($status === 'succeeded') {
            $this->finalizeIssuedRefund($refund, $payment, $result->providerReference);
            return;
        }

        if (in_array($status, ['pending', 'requires_action'], true)) {
            $refund->forceFill([
                'status' => 'processing',
                'provider_reference' => $result->providerReference ?: $refund->provider_reference,
                'processed_at' => null,
            ])->save();
            return;
        }

        if (in_array($status, ['failed', 'canceled'], true)) {
            $refund->forceFill([
                'status' => 'failed',
                'provider_reference' => $result->providerReference ?: $refund->provider_reference,
                'processed_at' => null,
                'notes' => trim(implode(' ', array_filter([
                    $refund->notes,
                    isset($result->metadata['failure_reason']) ? 'Provider failure: '.(string) $result->metadata['failure_reason'].'.' : null,
                ]))),
            ])->save();
            return;
        }

        throw new PaymentGatewayException('Payment provider returned an unsupported refund status.');
    }

    private function finalizeIssuedRefund(OrderRefund $refund, OrderPayment $payment, ?string $providerReference): void
    {
        DB::transaction(function () use ($refund, $payment, $providerReference): void {
            $lockedRefund = OrderRefund::query()->lockForUpdate()->findOrFail($refund->id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($lockedRefund->order_id);
            $wasIssued = $lockedRefund->status === 'issued';

            $lockedRefund->update([
                'order_payment_id' => $lockedPayment->id,
                'status' => 'issued',
                'provider_reference' => $providerReference ?: $lockedRefund->provider_reference,
                'processed_at' => $lockedRefund->processed_at ?: now(),
            ]);

            $paymentRefundedTotal = (float) OrderRefund::query()
                ->where('order_payment_id', $lockedPayment->id)
                ->where('status', 'issued')
                ->sum('amount');

            $lockedPayment->update([
                'refunded_amount' => min((float) $lockedPayment->amount, round($paymentRefundedTotal, 2)),
            ]);

            $issuedTotal = (float) $order->refunds()->where('status', 'issued')->sum('amount');
            $paidTotal = (float) $order->payments()->where('status', 'paid')->sum('amount');
            $order->update([
                'payment_status' => $paidTotal > 0 && round($issuedTotal, 2) >= round($paidTotal, 2)
                    ? 'refunded'
                    : 'partially_refunded',
            ]);

            if (! $lockedRefund->creditNote()->exists()) {
                $lockedRefund->creditNote()->create([
                    'order_id' => $order->id,
                    'credit_note_number' => $this->newCreditNoteNumber(),
                    'amount' => $lockedRefund->amount,
                    'currency' => $lockedRefund->currency,
                    'reason' => $lockedRefund->reason,
                    'issued_at' => now(),
                ]);
            }

            if (! $wasIssued) {
                $order->histories()->create([
                    'status' => $order->status,
                    'title' => 'Refund confirmed',
                    'description' => 'The payment provider confirmed refund '.$lockedRefund->refund_number.'.',
                    'metadata' => [
                        'order_refund_id' => $lockedRefund->id,
                        'order_payment_id' => $lockedPayment->id,
                        'provider' => $lockedPayment->provider,
                        'provider_reference' => $lockedRefund->provider_reference,
                    ],
                    'occurred_at' => now(),
                ]);
            }
        });
    }

    private function newRefundNumber(): string
    {
        do {
            $number = 'RFN-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (OrderRefund::query()->where('refund_number', $number)->exists());

        return $number;
    }

    private function newCreditNoteNumber(): string
    {
        do {
            $number = 'CN-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (\App\Models\OrderCreditNote::query()->where('credit_note_number', $number)->exists());

        return $number;
    }

    private function handleProviderSuccess(OrderPayment $payment, array $object): void
    {
        if (($object['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $amountMinor = isset($object['amount_total']) ? (int) $object['amount_total'] : null;
        $currency = isset($object['currency']) ? strtoupper((string) $object['currency']) : null;
        $this->validateProviderAmount($payment, $amountMinor, $currency);

        $this->markPaid(
            $payment,
            providerReference: (string) ($object['id'] ?? $payment->provider_reference),
            providerSessionId: (string) ($object['id'] ?? $payment->provider_session_id),
            providerPaymentId: is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : $payment->provider_payment_id,
        );
    }

    private function applyReconciledSuccess(OrderPayment $payment, GatewayPaymentStatus $status): void
    {
        $this->validateProviderAmount($payment, $status->amountMinor, $status->currency);
        $this->markPaid($payment, $status->providerReference, $status->providerSessionId, $status->providerPaymentId);
    }

    private function validateProviderAmount(OrderPayment $payment, ?int $amountMinor, ?string $currency): void
    {
        if ($amountMinor !== null && $amountMinor !== Money::toMinor((float) $payment->amount, (string) $payment->currency)) {
            throw new PaymentGatewayException('Provider amount does not match the server-side payment amount.');
        }

        if ($currency !== null && strtoupper($currency) !== strtoupper((string) $payment->currency)) {
            throw new PaymentGatewayException('Provider currency does not match the server-side payment currency.');
        }
    }

    private function markPaid(OrderPayment $payment, ?string $providerReference, ?string $providerSessionId, ?string $providerPaymentId): void
    {
        DB::transaction(function () use ($payment, $providerReference, $providerSessionId, $providerPaymentId): void {
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($lockedPayment->status === 'paid') {
                return;
            }

            $alreadyPaidByAnotherAttempt = $order->payment_status === 'paid'
                && $order->payments()->where('status', 'paid')->whereKeyNot($lockedPayment->id)->exists();

            $metadata = (array) $lockedPayment->metadata;
            if ($alreadyPaidByAnotherAttempt) {
                $metadata['duplicate_payment_review_required'] = true;
            }

            $lockedPayment->update([
                'provider_reference' => $providerReference ?: $lockedPayment->provider_reference,
                'provider_session_id' => $providerSessionId ?: $lockedPayment->provider_session_id,
                'provider_payment_id' => $providerPaymentId ?: $lockedPayment->provider_payment_id,
                'status' => 'paid',
                'paid_at' => now(),
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
                'metadata' => $metadata,
            ]);

            if ($alreadyPaidByAnotherAttempt) {
                $order->histories()->create([
                    'status' => $order->status,
                    'title' => 'Additional payment detected',
                    'description' => 'A second provider-confirmed payment was received after the order was already paid. Administrator refund/reconciliation review is required.',
                    'metadata' => ['order_payment_id' => $lockedPayment->id, 'provider' => $lockedPayment->provider],
                    'occurred_at' => now(),
                ]);

                return;
            }

            $nextStatus = in_array($order->status, ['pending_payment', 'payment_failed', 'payment_review'], true)
                ? 'payment_review'
                : $order->status;

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => $order->paid_at ?: now(),
                'status' => $nextStatus,
            ]);

            $order->histories()->create([
                'status' => $nextStatus,
                'title' => 'Payment confirmed',
                'description' => 'The payment provider confirmed the server-verified amount and currency. The order is ready for payment review.',
                'metadata' => ['order_payment_id' => $lockedPayment->id, 'provider' => $lockedPayment->provider],
                'occurred_at' => now(),
            ]);
        });
    }

    private function markStartUncertain(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = OrderPayment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment || $lockedPayment->status === 'paid') {
                return;
            }

            $lockedPayment->update([
                'status' => 'pending',
                'failure_code' => 'gateway_start_uncertain',
                'failure_message' => 'The provider response was interrupted. Retrying this attempt is safe because the same provider idempotency key will be reused.',
            ]);

            $order = Order::query()->lockForUpdate()->find($lockedPayment->order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'processing', 'status' => 'pending_payment']);
            }
        });
    }

    private function markFailed(OrderPayment $payment, string $code, string $message): void
    {
        DB::transaction(function () use ($payment, $code, $message): void {
            $lockedPayment = OrderPayment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment || $lockedPayment->status === 'paid') {
                return;
            }

            $lockedPayment->update([
                'status' => 'failed',
                'failure_code' => $code,
                'failure_message' => $message,
                'failed_at' => now(),
            ]);

            $order = Order::query()->lockForUpdate()->find($lockedPayment->order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'failed', 'status' => 'payment_failed']);
            }
        });
    }

    private function markExpired(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = OrderPayment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment || $lockedPayment->status === 'paid') {
                return;
            }

            $lockedPayment->update(['status' => 'expired']);
            $order = Order::query()->lockForUpdate()->find($lockedPayment->order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'pending', 'status' => 'pending_payment']);
            }
        });
    }
}
