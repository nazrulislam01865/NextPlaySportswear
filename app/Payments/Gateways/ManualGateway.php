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

final class ManualGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'manual';
    }

    public function configured(): bool
    {
        return true;
    }

    public function createCheckout(Order $order, OrderPayment $payment): GatewayRedirect
    {
        return new GatewayRedirect(null, $payment->provider_reference);
    }

    public function verifyWebhook(string $payload, string $signature): GatewayWebhook
    {
        throw new PaymentGatewayException('Manual payments do not accept provider webhooks.');
    }

    public function retrievePayment(OrderPayment $payment): GatewayPaymentStatus
    {
        return new GatewayPaymentStatus($payment->status, $payment->provider_reference);
    }

    public function refund(OrderPayment $payment, float $amount, string $reason = ''): GatewayRefundResult
    {
        throw new PaymentGatewayException('Manual refunds must be confirmed by an administrator after the external refund is completed.');
    }
}
