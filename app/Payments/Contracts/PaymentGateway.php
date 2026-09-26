<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Payments\DTO\GatewayPaymentStatus;
use App\Payments\DTO\GatewayRedirect;
use App\Payments\DTO\GatewayRefundResult;
use App\Payments\DTO\GatewayWebhook;

interface PaymentGateway
{
    public function code(): string;

    public function configured(): bool;

    public function createCheckout(Order $order, OrderPayment $payment): GatewayRedirect;

    public function verifyWebhook(string $payload, string $signature): GatewayWebhook;

    public function retrievePayment(OrderPayment $payment): GatewayPaymentStatus;

    public function refund(
        OrderPayment $payment,
        float $amount,
        string $reason = '',
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): GatewayRefundResult;
}
