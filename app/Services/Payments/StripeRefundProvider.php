<?php

namespace App\Services\Payments;

use App\Payments\Exceptions\PaymentGatewayException;
use Stripe\StripeClient;

/** Read-only Stripe API access. There is intentionally no create-refund method. */
class StripeRefundProvider
{
    private function client(): StripeClient
    {
        $key = (string) config('payments.gateways.stripe.secret_key');
        if (! config('payments.gateways.stripe.enabled') || $key === '' || ! class_exists(StripeClient::class)) {
            throw new PaymentGatewayException('Stripe refund verification is not configured.');
        }

        return new StripeClient($key);
    }

    public function refund(string $refundId): array
    {
        return $this->client()->refunds->retrieve($refundId, [])->toArray();
    }

    public function charge(string $chargeId): array
    {
        return $this->client()->charges->retrieve($chargeId, [])->toArray();
    }

    /** The charge event is only a fallback; refund.created/updated is primary. */
    public function refundIdsForCharge(string $chargeId): array
    {
        $page = $this->client()->refunds->all(['charge' => $chargeId, 'limit' => 100]);
        if ($page->has_more) {
            throw new PaymentGatewayException('Charge has more refunds than one reconciliation page. Reconcile separately.');
        }

        return array_map(static fn ($item) => (string) $item->id, $page->data);
    }
}
