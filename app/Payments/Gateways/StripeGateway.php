<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTO\GatewayPaymentStatus;
use App\Payments\DTO\GatewayRedirect;
use App\Payments\DTO\GatewayRefundResult;
use App\Payments\DTO\GatewayWebhook;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\Money;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;

final class StripeGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'stripe';
    }

    public function configured(): bool
    {
        return (bool) config('payments.gateways.stripe.enabled')
            && filled(config('payments.gateways.stripe.secret_key'))
            && filled(config('payments.gateways.stripe.webhook_secret'))
            && class_exists(StripeClient::class)
            && class_exists(Webhook::class);
    }

    public function createCheckout(Order $order, OrderPayment $payment): GatewayRedirect
    {
        $this->assertConfigured();

        try {
            $stripe = $this->client();
            $configuredCurrency = strtoupper((string) config('payments.gateways.stripe.currency', 'USD'));
            $paymentCurrency = strtoupper((string) $payment->currency);

            if ($configuredCurrency !== $paymentCurrency) {
                throw new PaymentGatewayException('Stripe currency configuration does not match the server-side order currency.');
            }

            $currency = strtolower($paymentCurrency);
            $amountMinor = Money::toMinor((string) $payment->amount, $paymentCurrency);

            if ($amountMinor <= 0) {
                throw new PaymentGatewayException('Stripe checkout requires a positive server-side payment amount.');
            }
            $metadata = [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'payment_id' => (string) $payment->id,
            ];
            if ($payment->order_payment_installment_id) {
                $metadata['installment_id'] = (string) $payment->order_payment_installment_id;
            }

            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'client_reference_id' => (string) $order->order_number,
                'customer_email' => (string) $order->customer_email,
                'success_url' => route('payments.return', ['provider' => 'stripe']).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('account.orders.show', $order).'?payment=cancelled',
                'expires_at' => now()->addMinutes(max(30, min(1440, (int) config('payments.gateways.stripe.checkout_expires_minutes', 30))))->timestamp,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $amountMinor,
                        'product_data' => [
                            'name' => 'NextPlay Order '.$order->order_number,
                            'description' => $payment->order_payment_installment_id
                                ? 'Customer-accepted approved installment'
                                : 'Server-confirmed final order total',
                        ],
                    ],
                ]],
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ], [
                'idempotency_key' => $payment->idempotency_key,
            ]);

            return new GatewayRedirect(
                url: $session->url,
                providerReference: $session->id,
                providerSessionId: $session->id,
                providerPaymentId: is_string($session->payment_intent) ? $session->payment_intent : null,
                metadata: [
                    'expires_at' => $session->expires_at,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);
            throw new PaymentGatewayException('Stripe could not start the secure checkout session. Please try again.', previous: $exception);
        }
    }

    public function verifyWebhook(string $payload, string $signature): GatewayWebhook
    {
        $this->assertConfigured();

        if ($signature === '') {
            throw new PaymentGatewayException('Stripe webhook signature is missing.');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                (string) config('payments.gateways.stripe.webhook_secret'),
            );

            $key = (string) config('payments.gateways.stripe.secret_key');
            $isLiveKey = str_starts_with($key, 'sk_live_') || str_starts_with($key, 'rk_live_');
            $isTestKey = str_starts_with($key, 'sk_test_') || str_starts_with($key, 'rk_test_');
            if ((! $isLiveKey && ! $isTestKey) || (bool) $event->livemode !== $isLiveKey) {
                // Invalid/ambiguous API key provenance must never bypass mode matching.
                throw new PaymentGatewayException('Stripe webhook mode does not match the configured API key.');
            }
            if (filled($event->account ?? null)) {
                // Connected-account events cannot be linked to this single-account store.
                throw new PaymentGatewayException('Connected-account events are not supported.');
            }

            return new GatewayWebhook(
                providerEventId: (string) $event->id,
                type: (string) $event->type,
                payload: $event->toArray(),
            );
        } catch (Throwable $exception) {
            throw new PaymentGatewayException('Stripe webhook signature verification failed.', previous: $exception);
        }
    }

    public function retrievePayment(OrderPayment $payment): GatewayPaymentStatus
    {
        $this->assertConfigured();

        if (! filled($payment->provider_session_id)) {
            throw new PaymentGatewayException('Stripe payment has no Checkout Session reference.');
        }

        try {
            $session = $this->client()->checkout->sessions->retrieve((string) $payment->provider_session_id, []);
            // Checkout is the authority for a hosted session. A mismatched
            // returned object must not be allowed to confirm another order.
            $metadata = $session->metadata?->toArray() ?? [];
            if ((string) ($metadata['payment_id'] ?? '') !== (string) $payment->id
                || (string) ($metadata['order_id'] ?? '') !== (string) $payment->order_id
                || (string) ($session->client_reference_id ?? '') !== (string) $payment->order->order_number) {
                throw new PaymentGatewayException('Stripe Session does not belong to the expected order and payment.');
            }
            if ($payment->order_payment_installment_id &&
                (string) ($metadata['installment_id'] ?? '') !== (string) $payment->order_payment_installment_id) {
                throw new PaymentGatewayException('Stripe Session installment reference mismatch.');
            }
            if ($payment->provider_payment_id && is_string($session->payment_intent)
                && $payment->provider_payment_id !== $session->payment_intent) {
                throw new PaymentGatewayException('Stripe Session PaymentIntent reference mismatch.');
            }
            $status = match (true) {
                (string) $session->payment_status === 'paid' => 'paid',
                (string) $session->status === 'expired' => 'expired',
                default => 'processing',
            };

            return new GatewayPaymentStatus(
                status: $status,
                providerReference: (string) $session->id,
                providerSessionId: (string) $session->id,
                providerPaymentId: is_string($session->payment_intent) ? $session->payment_intent : null,
                amountMinor: isset($session->amount_total) ? (int) $session->amount_total : null,
                currency: isset($session->currency) ? strtoupper((string) $session->currency) : null,
                metadata: $metadata,
            );
        } catch (Throwable $exception) {
            report($exception);
            throw new PaymentGatewayException('Stripe payment status could not be reconciled.', previous: $exception);
        }
    }

    /** Stripe Dashboard is the sole permitted refund initiation channel. */
    public function refund(
        OrderPayment $payment,
        float $amount,
        string $reason = '',
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): GatewayRefundResult
    {
        throw new PaymentGatewayException(
            'Application-initiated Stripe refunds are disabled. An authorized finance owner must refund in Stripe Dashboard.',
        );
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new PaymentGatewayException('Stripe is not fully configured. Install stripe/stripe-php and set STRIPE_ENABLED, STRIPE_SECRET_KEY, and STRIPE_WEBHOOK_SECRET.');
        }
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) config('payments.gateways.stripe.secret_key'));
    }
}
