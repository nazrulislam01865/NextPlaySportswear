<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTO\GatewayPaymentStatus;
use App\Payments\DTO\GatewayRedirect;
use App\Payments\DTO\GatewayRefundResult;
use App\Payments\DTO\GatewayWebhook;

final class FakeStripeGateway implements PaymentGateway
{
    public static array $refundCalls = [];

    public static string $refundStatus = 'succeeded';

    public static function reset(): void
    {
        self::$refundCalls = [];
        self::$refundStatus = 'succeeded';
    }

    public function code(): string
    {
        return 'stripe';
    }

    public function configured(): bool
    {
        return true;
    }

    public function createCheckout(Order $order, OrderPayment $payment): GatewayRedirect
    {
        return new GatewayRedirect(
            url: 'https://checkout.stripe.test/session/'.$payment->id,
            providerReference: 'cs_test_'.$payment->id,
            providerSessionId: 'cs_test_'.$payment->id,
            providerPaymentId: 'pi_test_'.$payment->id,
            metadata: ['expires_at' => now()->addMinutes(30)->timestamp],
        );
    }

    public function verifyWebhook(string $payload, string $signature): GatewayWebhook
    {
        return new GatewayWebhook('evt_test', 'checkout.session.completed', []);
    }

    public function retrievePayment(OrderPayment $payment): GatewayPaymentStatus
    {
        return new GatewayPaymentStatus('paid', $payment->provider_reference, $payment->provider_session_id, $payment->provider_payment_id);
    }

    public function refund(
        OrderPayment $payment,
        float $amount,
        string $reason = '',
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): GatewayRefundResult {
        self::$refundCalls[] = compact('payment', 'amount', 'reason', 'idempotencyKey', 'metadata');

        return new GatewayRefundResult(
            self::$refundStatus,
            're_test_'.count(self::$refundCalls),
        );
    }
}
