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
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\Money;
use App\Payments\PaymentGatewayManager;
use App\Services\Rewards\RewardService;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PaymentOrchestrator
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly RewardService $rewards,
        private readonly FinancialStateService $financialState,
        private readonly StripeRefundSynchronizer $stripeRefunds,
        private readonly ApprovedInstallmentService $installments,
    ) {
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
            'payment_intent.payment_failed',
            'payment_intent.succeeded',
            'refund.created',
            'refund.updated',
            'refund.failed',
            'charge.refund.updated',
            'charge.refunded',
        ];

        // Stripe can be configured to send extra event types. A valid signed
        // but irrelevant event should be acknowledged, not retried as an error.
        if (! in_array($webhook->event_type, $supportedEvents, true)) {
            return;
        }

        $payload = (array) $webhook->payload;
        // This integration uses one Stripe account only; never cross-link a
        // Stripe Connect account's refund to a platform order.
        if (filled($payload['account'] ?? null)) {
            throw new PaymentGatewayException('Connected-account financial events are not supported.');
        }
        $object = (array) data_get($payload, 'data.object', []);
        if (in_array($webhook->event_type, ['refund.created', 'refund.updated', 'refund.failed', 'charge.refund.updated'], true)) {
            $payment = $this->stripeRefunds->syncRefund((string) ($object['id'] ?? ''));
            if ($payment) {
                $webhook->forceFill(['order_payment_id' => $payment->id])->save();
            }
            return;
        }
        if ($webhook->event_type === 'charge.refunded') {
            $this->stripeRefunds->syncCharge((string) ($object['id'] ?? ''));
            return;
        }
        $payment = $this->findWebhookPayment($webhook->provider, $object, $webhook->event_type);

        if (! $payment instanceof OrderPayment) {
            throw new PaymentGatewayException('Webhook could not be matched to an internal payment attempt.');
        }

        $webhook->forceFill(['order_payment_id' => $payment->id])->save();

        match ($webhook->event_type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->handleProviderSuccess($payment, $object),
            'payment_intent.payment_failed' => $this->recordRecoverableDecline($payment, $object, $webhook),
            'payment_intent.succeeded' => $this->reconcileSucceededIntent($payment, $object),
            'checkout.session.async_payment_failed' => $this->markFailed($payment, 'provider_failed', 'Stripe reported that the payment failed.'),
            'checkout.session.expired' => $this->markExpired($payment),
            default => null,
        };
    }

    /**
     * Phase 1: NextPlay must never issue refunds. The finance owner issues them
     * directly in Stripe Dashboard. Provider webhook reconciliation remains
     * separate and will be extended under a later approved phase.
     */
    public function issueReturnRefund(OrderReturnRequest $returnRequest, float $amount): OrderRefund
    {
        throw new PaymentGatewayException(
            'Refund initiation from NextPlay is disabled. Refunds must be issued by the authorized Stripe finance owner.',
        );
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


        $provider = (string) $method->provider;
        $gateway = $this->gateways->gateway($provider);

        if (! $gateway->configured()) {
            throw ValidationException::withMessages([
                'payment_method' => ucfirst($provider).' is not configured on this server yet.',
            ]);
        }

        // Resolve an expired/uncertain live Checkout Session before allocating
        // another attempt. Never guess that a remote payment has failed.
        $previous = $order->payments()
            ->whereIn('status', ['pending', 'processing'])->latest('id')->first();
        if ($previous instanceof OrderPayment && $previous->provider === 'stripe'
            && $previous->provider_session_id && $previous->expires_at?->isPast()) {
            $this->reconcile($previous);
        }

        $payment = $this->createAttempt($order, $method, $idempotencySeed, $snapshot);

        if ($payment->status === 'paid') {
            return new GatewayRedirect(null, $payment->provider_reference, $payment->provider_session_id, $payment->provider_payment_id);
        }
        if (in_array($payment->status, ['failed', 'expired'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'This payment session is closed. Refresh the payment page to start a new attempt.']);
        }

        $existingUrl = data_get($payment->metadata, 'checkout_url');
        if ($payment->status === 'processing' && is_string($existingUrl) && $existingUrl !== '') {
            if ($payment->expires_at === null || $payment->expires_at->isFuture()) {
                return new GatewayRedirect($existingUrl, $payment->provider_reference, $payment->provider_session_id, $payment->provider_payment_id);
            }
            // The session may have completed remotely despite local expiry.
            $this->reconcile($payment);
            throw ValidationException::withMessages(['payment_method' => 'The previous checkout session has expired or is still being verified. Refresh the page before trying again.']);
        }
        if ($payment->provider_session_id) {
            throw ValidationException::withMessages(['payment_method' => 'A previous checkout is still being verified. Please try again shortly.']);
        }

        if ($provider === 'manual') {
            return $gateway->createCheckout($order, $payment);
        }

        try {
            $redirect = $gateway->createCheckout($order->fresh(), $payment->fresh());

            DB::transaction(function () use ($order, $payment, $redirect): void {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
                // A signed webhook may win the race against the Session create
                // HTTP response. Never rewind a receipt or terminal attempt.
                if (! in_array($lockedPayment->status, ['pending', 'processing'], true)) {
                    return;
                }
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
                if (in_array($lockedOrder->status, ['pending_payment', 'payment_failed', 'payment_review', 'quote_invoice_requested'], true)
                    && in_array($lockedOrder->payment_status, ['pending', 'processing', 'failed'], true)) {
                    $lockedOrder->forceFill(['payment_status' => 'processing', 'status' => 'pending_payment'])->save();
                }
            });

            $updated = $payment->fresh();
            if ($updated->status === 'paid') {
                return new GatewayRedirect(null, $updated->provider_reference, $updated->provider_session_id, $updated->provider_payment_id);
            }
            if (! in_array($updated->status, ['pending', 'processing'], true)) {
                throw ValidationException::withMessages(['payment_method' => 'The provider has already closed this payment attempt. Refresh before retrying.']);
            }
            if (! in_array($updated->order->status, ['pending_payment', 'payment_failed', 'payment_review', 'quote_invoice_requested'], true)) {
                throw ValidationException::withMessages(['payment_method' => 'The order changed while starting checkout. Please review the order before paying.']);
            }
            return $redirect;
        } catch (Throwable $exception) {
            // A network timeout does not prove Stripe failed to create the
            // Session. Keep this attempt resumable and reuse the SAME gateway
            // idempotency key on the next try to prevent double charges.
            $this->markStartUncertain($payment);
            throw $exception;
        }
    }

    private function createAttempt(Order $order, PaymentMethod $method, string $idempotencySeed, array $snapshot): OrderPayment
    {
        $attemptKey = null;
        try {
            return DB::transaction(function () use ($order, $method, $idempotencySeed, $snapshot, &$attemptKey): OrderPayment {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                $installment = null;
                $scheduleExists = false;
                if (config('payments.installments.enabled', false)) {
                    $this->installments->ensureForOrder($locked);
                    $installment = $this->installments->nextDue($locked);
                    $scheduleExists = $locked->installmentSchedule()->exists();
                }
                $attemptKey = hash('sha256', 'nextplay|'.$locked->id.'|'.$method->id.'|'.($installment?->id ?? 'full').'|'.$idempotencySeed);

                if ($locked->payment_status === 'paid') {
                    $paid = $locked->payments()->where('status', 'paid')->latest()->first();
                    if ($paid instanceof OrderPayment) {
                        return $paid;
                    }
                }

                if (! $locked->canPay() && $locked->payment_status !== 'processing') {
                    throw ValidationException::withMessages(['payment_method' => 'This order is not currently eligible for payment.']);
                }
                $financial = $this->financialState->forOrder($locked);
                if ($financial['outstanding_minor'] <= 0 || ($scheduleExists && ! $installment)) {
                    throw ValidationException::withMessages(['payment_method' => 'This order has no remaining verified payment obligation.']);
                }
                // Unscheduled partial receipts never authorize arbitrary top-ups.
                if ($financial['gross_captured_minor'] > 0 && ! $scheduleExists) {
                    throw ValidationException::withMessages(['payment_method' => 'A partial receipt needs an authorized installment schedule before a further payment.']);
                }

                if ($scheduleExists && $method->provider !== 'stripe') {
                    throw ValidationException::withMessages(['payment_method' => 'Approved installments must be paid through Stripe Checkout.']);
                }
                $payableAmount = $installment
                    ? Money::fromMinor((int) $installment->amount_minor, (string) $locked->currency)
                    : $locked->outstandingAmount();
                if (($method->minimum_total !== null && $payableAmount < (float) $method->minimum_total)
                    || ($method->maximum_total !== null && $payableAmount > (float) $method->maximum_total)) {
                    throw ValidationException::withMessages(['payment_method' => 'The method is unavailable for the approved payment amount.']);
                }
                $existing = $locked->payments()->where('idempotency_key', $attemptKey)->first();
                if ($existing instanceof OrderPayment) {
                    return $existing;
                }

                // Never create a second live provider payment while another
                // attempt for the same method may still complete remotely.
                $inFlight = $locked->payments()
                    ->whereIn('status', ['pending', 'processing'])
                    ->latest('id')->first();
                // A live attempt for a different method can also settle; do
                // not open two payment paths for the same outstanding debt.
                if ($inFlight instanceof OrderPayment) {
                    if ((int) $inFlight->payment_method_id !== (int) $method->id) {
                        throw ValidationException::withMessages([
                            'payment_method' => 'An existing payment is still being verified. Complete or expire that session before choosing another method.',
                        ]);
                    }
                    return $inFlight;
                }

                $capabilities = $this->gateways->capabilities((string) $method->provider);
                $manual = (bool) $capabilities['requires_manual_review'];
                $payment = $locked->payments()->create([
                    'payment_method_id' => $method->id,
                    'gateway' => $method->provider,
                    'provider' => $method->provider,
                    'idempotency_key' => $attemptKey,
                    'status' => 'pending',
                    'amount' => $payableAmount,
                    'order_payment_installment_id' => $installment?->id,
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
            $existing = $attemptKey ? OrderPayment::query()->where('idempotency_key', $attemptKey)->first() : null;
            if ($existing instanceof OrderPayment) {
                return $existing;
            }
            throw $exception;
        }
    }

    private function findWebhookPayment(string $provider, array $object, string $eventType): ?OrderPayment
    {
        $isIntent = str_starts_with($eventType, 'payment_intent.');
        $isSession = str_starts_with($eventType, 'checkout.session.');
        $objectId = (string) ($object['id'] ?? '');
        if (($isSession && ! str_starts_with($objectId, 'cs_'))
            || ($isIntent && ! str_starts_with($objectId, 'pi_'))) {
            throw new PaymentGatewayException('Webhook provider object ID is missing or invalid.');
        }
        $metadataId = (int) (data_get($object, 'metadata.payment_id') ?: data_get($object, 'metadata.order_payment_id', 0));
        $payment = $metadataId > 0
            ? OrderPayment::query()->whereKey($metadataId)->where('provider', $provider)->first()
            : null;
        if (! $payment instanceof OrderPayment) {
            $intentId = $isIntent ? $objectId : (string) ($object['payment_intent'] ?? '');
            if ($intentId !== '') {
                $payment = OrderPayment::query()->where('provider', $provider)
                    ->where('provider_payment_id', $intentId)->first();
            }
        }
        if (! $payment instanceof OrderPayment && $isSession && $objectId !== '') {
            $payment = OrderPayment::query()->where('provider', $provider)
                ->where('provider_session_id', $objectId)->first();
        }
        if (! $payment instanceof OrderPayment) {
            return null;
        }
        if ($metadataId > 0 && (int) $payment->id !== $metadataId) {
            throw new PaymentGatewayException('Webhook payment reference conflicts with its provider object.');
        }
        $metadataOrder = data_get($object, 'metadata.order_id');
        if ($metadataOrder !== null && (string) $metadataOrder !== (string) $payment->order_id) {
            throw new PaymentGatewayException('Webhook order association does not match the payment attempt.');
        }
        if ($isIntent && $payment->provider_payment_id && $payment->provider_payment_id !== $objectId) {
            throw new PaymentGatewayException('Webhook PaymentIntent does not match the original attempt.');
        }
        if ($isSession && $payment->provider_session_id && $payment->provider_session_id !== $objectId) {
            throw new PaymentGatewayException('Webhook Checkout Session does not match the original attempt.');
        }
        if ($payment->order_payment_installment_id &&
            (string) data_get($object, 'metadata.installment_id') !== (string) $payment->order_payment_installment_id) {
            throw new PaymentGatewayException('Webhook installment reference does not match the approved schedule.');
        }
        return $payment;
    }

    /**
     * A Stripe card decline can occur while Checkout is still open. Keep the
     * attempt processing and preserve its provider history; do not fail the
     * order or stop the customer from trying another card in the same session.
     */
    private function recordRecoverableDecline(OrderPayment $payment, array $object, PaymentWebhookEvent $webhook): void
    {
        $code = (string) (data_get($object, 'last_payment_error.decline_code')
            ?: data_get($object, 'last_payment_error.code') ?: 'payment_declined');
        $code = preg_match('/^[a-z0-9_]{1,80}$/', $code) ? $code : 'payment_declined';

        DB::transaction(function () use ($payment, $webhook, $code): void {
            $locked = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === 'paid') {
                return;
            }
            $meta = (array) $locked->metadata;
            if (($meta['last_decline_event_id'] ?? null) === $webhook->provider_event_id) {
                return;
            }
            $meta['last_decline_event_id'] = $webhook->provider_event_id;
            $meta['last_decline_code'] = $code;
            $meta['last_decline_at'] = now()->toIso8601String();
            $meta['decline_count'] = min(100000, (int) ($meta['decline_count'] ?? 0) + 1);
            $locked->forceFill([
                'metadata' => $meta,
                'failure_code' => 'attempt_declined',
                'failure_message' => 'A payment method was declined. The customer may try again in the active checkout session.',
            ])->save();
            app(PaymentAuditRecorder::class)->record(
                'stripe:decline:'.hash('sha256', (string) $webhook->provider_event_id),
                'payment_declined', $locked->order_id, $locked->id,
                providerReference: $locked->provider_payment_id,
                oldStatus: (string) $locked->status, newStatus: (string) $locked->status,
                currency: (string) $locked->currency,
            );
        });
    }

    /** PaymentIntent success must be reconciled against the parent Checkout Session. */
    private function reconcileSucceededIntent(OrderPayment $payment, array $object): void
    {
        $amount = isset($object['amount_received']) ? (int) $object['amount_received'] : null;
        $currency = isset($object['currency']) ? strtoupper((string) $object['currency']) : null;
        $this->validateProviderAmount($payment, $amount, $currency);
        if (! $payment->provider_session_id) {
            throw new PaymentGatewayException('PaymentIntent success is pending a matching Checkout Session reference.');
        }
        if ($payment->status === 'paid') {
            return;
        }
        // A preceding async-failure notification may have marked this
        // attempt failed. A later verified success must still win.
        $gateway = $this->gateways->gateway('stripe');
        $session = $gateway->retrievePayment($payment);
        if (! $session->providerPaymentId || $session->providerPaymentId !== (string) ($object['id'] ?? '')) {
            throw new PaymentGatewayException('Stripe PaymentIntent does not match its verified Checkout Session.');
        }
        if ($session->status !== 'paid') {
            throw new PaymentGatewayException('Stripe PaymentIntent success is awaiting confirmed Checkout settlement.');
        }
        $this->applyReconciledSuccess($payment, $session);
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
        if ($payment->provider === 'stripe' && ($amountMinor === null || $currency === null)) {
            throw new PaymentGatewayException('Stripe confirmation did not include a verifiable amount and currency.');
        }

        if ($amountMinor !== null && $amountMinor !== Money::toMinor((string) $payment->amount, (string) $payment->currency)) {
            throw new PaymentGatewayException('Provider amount does not match the server-side payment amount.');
        }

        if ($currency !== null && strtoupper($currency) !== strtoupper((string) $payment->currency)) {
            throw new PaymentGatewayException('Provider currency does not match the server-side payment currency.');
        }
    }

    private function markPaid(OrderPayment $payment, ?string $providerReference, ?string $providerSessionId, ?string $providerPaymentId): void
    {
        DB::transaction(function () use ($payment, $providerReference, $providerSessionId, $providerPaymentId): void {
            // Lock order first: serialize different payment attempts on the
            // same order and avoid double payment/workflow transitions.
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'paid') {
                return;
            }
            if ($lockedPayment->provider !== 'stripe') {
                throw new PaymentGatewayException('Only verified Stripe payments can use automatic paid confirmation.');
            }
            if ($lockedPayment->provider_session_id && $providerSessionId
                && $lockedPayment->provider_session_id !== $providerSessionId) {
                throw new PaymentGatewayException('Confirmed Session does not match the persisted attempt.');
            }
            if ($lockedPayment->provider_payment_id && $providerPaymentId
                && $lockedPayment->provider_payment_id !== $providerPaymentId) {
                throw new PaymentGatewayException('Confirmed PaymentIntent does not match the persisted attempt.');
            }

            $oldStatus = (string) $order->status;
            $oldPaymentStatus = (string) $order->payment_status;
            $oldFulfillmentStatus = (string) $order->fulfillment_status;
            $before = $this->financialState->forOrder($order);
            $duplicate = $before['outstanding_minor'] === 0 || $before['collection_status'] === 'legacy_unverified';
            $metadata = (array) $lockedPayment->metadata;
            if ($duplicate) {
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

            app(PaymentAuditRecorder::class)->record(
                'stripe:payment:'.$lockedPayment->id.':paid',
                'payment_confirmed', $order->id, $lockedPayment->id,
                providerReference: $lockedPayment->provider_payment_id ?: $lockedPayment->provider_session_id,
                oldStatus: (string) $payment->status, newStatus: 'paid',
                amountMinor: Money::toMinor((string) $lockedPayment->amount, (string) $lockedPayment->currency),
                currency: (string) $lockedPayment->currency,
            );
            $state = $this->financialState->forOrder($order);
            $newPaymentStatus = $this->financialState->legacyStatus($state, $oldPaymentStatus);
            $eligibleFulfillment = in_array($oldStatus, ['pending_payment', 'payment_failed', 'payment_review'], true);
            // Do not release a cancelled/held order or a deposit into production.
            $scheduleVerified = true;
            $scheduleException = false;
            if (config('payments.installments.enabled', false) || $lockedPayment->order_payment_installment_id) {
                try {
                    $scheduleVerified = $this->installments->isFullyPaid($order);
                } catch (PaymentGatewayException $exception) {
                    // A genuine provider capture is never discarded just because
                    // local installment allocation needs manual reconciliation.
                    $scheduleVerified = false;
                    $scheduleException = true;
                    $metadata = (array) $lockedPayment->metadata;
                    $metadata['installment_review_required'] = true;
                    $lockedPayment->forceFill(['metadata' => $metadata])->save();
                    report($exception);
                }
            }
            $fullySettled = $state['collection_status'] === 'paid' && ! $duplicate && $scheduleVerified;
            $nextStatus = $fullySettled && $eligibleFulfillment ? 'design_review' : $oldStatus;
            if ($scheduleException && $eligibleFulfillment) {
                $nextStatus = 'payment_review';
            }
            if ($duplicate) {
                $nextStatus = $oldStatus;
            }

            $order->forceFill([
                'payment_status' => $newPaymentStatus,
                'paid_at' => $fullySettled ? ($order->paid_at ?: now()) : $order->paid_at,
                'status' => $nextStatus,
            ])->save();
            if ($fullySettled && $eligibleFulfillment) {
                $this->rewards->redeemOrderReservation($order);
            }

            $order->histories()->create([
                'status' => $nextStatus,
                'title' => $duplicate ? 'Additional payment detected' : ($fullySettled ? 'Payment confirmed' : 'Partial payment received'),
                'description' => $duplicate
                    ? 'An additional provider-confirmed payment requires finance reconciliation in Stripe Dashboard.'
                    : ($fullySettled
                        ? ($nextStatus === 'design_review'
                            ? 'The provider confirmed the amount and currency. The order moved to design review.'
                            : 'The provider confirmed the amount and currency. The order is fully paid.')
                        : 'The provider confirmed a receipt, but the order still has an outstanding balance.'),
                'metadata' => ['order_payment_id' => $lockedPayment->id, 'provider' => 'stripe'],
                'occurred_at' => now(),
            ]);
            $this->notifyAfterCommit($order, $oldStatus, $oldPaymentStatus, $oldFulfillmentStatus);
        });
    }

    private function notifyAfterCommit(Order $order, string $oldStatus, string $oldPaymentStatus, string $oldFulfillmentStatus): void
    {
        if ($oldStatus === $order->status && $oldPaymentStatus === $order->payment_status) {
            return;
        }
        DB::afterCommit(function () use ($order, $oldStatus, $oldPaymentStatus, $oldFulfillmentStatus): void {
            try {
                app(TransactionalEmailManager::class)->orderUpdated(
                    $order->fresh(), $oldStatus, $oldPaymentStatus, $oldFulfillmentStatus,
                );
            } catch (Throwable $exception) {
                report($exception); // Email never rolls back a verified financial event.
            }
        });
    }

    private function markStartUncertain(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! in_array($lockedPayment->status, ['pending', 'processing'], true)) {
                return; // Never rewind a terminal provider result.
            }
            $lockedPayment->forceFill([
                'status' => 'pending',
                'failure_code' => 'gateway_start_uncertain',
                'failure_message' => 'The provider response was interrupted. Reuse the same provider idempotency key when retrying.',
            ])->save();
            $state = $this->financialState->forOrder($order);
            $next = $this->financialState->legacyStatus($state, (string) $order->payment_status);
            $order->forceFill(['payment_status' => $next])->save();
        });
    }

    private function markFailed(OrderPayment $payment, string $code, string $message): void
    {
        DB::transaction(function () use ($payment, $code, $message): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status === 'paid') {
                return;
            }
            $oldStatus = (string) $order->status;
            $oldPaymentStatus = (string) $order->payment_status;
            $previousAttemptStatus = (string) $lockedPayment->status;
            $lockedPayment->update([
                'status' => 'failed',
                'failure_code' => $code,
                'failure_message' => $message,
                'failed_at' => now(),
            ]);
            if ($previousAttemptStatus !== 'failed') {
                app(PaymentAuditRecorder::class)->record(
                    'stripe:payment:'.$lockedPayment->id.':failed',
                    'payment_failed', $order->id, $lockedPayment->id,
                    providerReference: $lockedPayment->provider_payment_id ?: $lockedPayment->provider_session_id,
                    oldStatus: $previousAttemptStatus, newStatus: 'failed',
                    currency: (string) $lockedPayment->currency,
                );
            }
            $state = $this->financialState->forOrder($order);
            $nextPaymentStatus = $this->financialState->legacyStatus($state, $oldPaymentStatus);
            $orderStatus = $oldStatus;
            if ($nextPaymentStatus === 'failed' && in_array($oldStatus, ['pending_payment', 'payment_failed'], true)) {
                $orderStatus = 'payment_failed';
            }
            $order->forceFill(['payment_status' => $nextPaymentStatus, 'status' => $orderStatus])->save();
            $this->notifyAfterCommit($order, $oldStatus, $oldPaymentStatus, (string) $order->fulfillment_status);
        });
    }

    private function markExpired(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status === 'paid') {
                return;
            }
            $oldStatus = (string) $order->status;
            $oldPaymentStatus = (string) $order->payment_status;
            $wasExpired = $lockedPayment->status === 'expired';
            $priorStatus = (string) $lockedPayment->status;
            $lockedPayment->forceFill(['status' => 'expired'])->save();
            if (! $wasExpired) {
                app(PaymentAuditRecorder::class)->record(
                    'stripe:payment:'.$lockedPayment->id.':expired',
                    'payment_expired', $order->id, $lockedPayment->id,
                    providerReference: $lockedPayment->provider_session_id,
                    oldStatus: $priorStatus, newStatus: 'expired',
                    currency: (string) $lockedPayment->currency,
                );
            }
            $state = $this->financialState->forOrder($order);
            $nextPaymentStatus = $this->financialState->legacyStatus($state, $oldPaymentStatus);
            $newStatus = in_array($oldStatus, ['pending_payment', 'payment_failed'], true) && $nextPaymentStatus === 'pending'
                ? 'pending_payment' : $oldStatus;
            $order->forceFill(['payment_status' => $nextPaymentStatus, 'status' => $newStatus])->save();
            $this->notifyAfterCommit($order, $oldStatus, $oldPaymentStatus, (string) $order->fulfillment_status);
        });
    }
}
