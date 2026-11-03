<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Payments\Money;
use App\Payments\Exceptions\PaymentGatewayException;

/**
 * Read-only financial projection over persisted attempts and confirmed refunds.
 * Never accepts payment amounts/statuses from HTTP or CMS content.
 * A missing installment contract means the original order grand total remains due.
 */
final class FinancialStateService
{
    /** @return array{currency:string,gross_captured_minor:int,confirmed_refunded_minor:int,net_received_minor:int,outstanding_minor:int,collection_status:string,refund_status:string,legacy_unverified_receipts:bool} */
    public function forOrder(Order $order): array
    {
        $currency = strtoupper((string) $order->currency);
        $total = Money::toMinor((string) $order->grand_total, $currency);
        $captured = 0;
        $legacyUnverified = false;
        $attempts = $order->payments()->orderByDesc('id')->get();

        foreach ($attempts as $payment) {
            if ($payment->status !== 'paid') {
                continue;
            }

            if (strtoupper((string) $payment->currency) !== $currency) {
                throw new PaymentGatewayException('Payment record currency differs from its order. Financial review required.');
            }

            // Historical administrative entries are preserved for audit and
            // compatibility, but explicitly flagged as unverified provenance.
            if (! in_array($payment->provider, ['stripe'], true)) {
                $legacyUnverified = true;
            }
            $captured += Money::toMinor((string) $payment->amount, $currency);
        }

        $confirmedRefunded = 0;
        $pendingRefund = false;
        $failedRefund = false;
        foreach ($order->refunds()->get() as $refund) {
            if (strtoupper((string) $refund->currency) !== $currency) {
                throw new PaymentGatewayException('Refund record currency differs from its order. Financial review required.');
            }
            if ($refund->status === 'issued') {
                $confirmedRefunded += Money::toMinor((string) $refund->amount, $currency);
            } elseif (in_array($refund->status, ['processing', 'pending'], true)) {
                $pendingRefund = true;
            } elseif ($refund->status === 'failed') {
                $failedRefund = true;
            }
        }
        if ($confirmedRefunded > $captured) {
            throw new PaymentGatewayException('Confirmed refunds exceed recorded receipts. Financial review required.');
        }

        $latest = $attempts->first();
        $collection = match (true) {
            $total > 0 && $captured >= $total => 'paid',
            $captured > 0 => 'partially_paid',
            $attempts->contains(fn ($attempt) => in_array($attempt->status, ['processing', 'pending'], true)) => 'processing',
            $latest?->status === 'failed' => 'failed',
            default => 'pending',
        };
        // Preserve the recorded state of imported historical orders without
        // payment rows. Do not silently rewrite their accounting history.
        if ($attempts->isEmpty() && in_array($order->payment_status, ['paid', 'refunded', 'partially_refunded'], true)) {
            $collection = 'legacy_unverified';
            $legacyUnverified = true;
        }

        $refundStatus = match (true) {
            $captured > 0 && $confirmedRefunded === $captured => 'refunded',
            $confirmedRefunded > 0 => 'partially_refunded',
            $pendingRefund => 'pending',
            $failedRefund => 'failed',
            default => 'none',
        };

        return [
            'currency' => $currency,
            'gross_captured_minor' => $captured,
            'confirmed_refunded_minor' => $confirmedRefunded,
            'net_received_minor' => $captured - $confirmedRefunded,
            // Refunds do not automatically reinstate the commercial obligation.
            'outstanding_minor' => max(0, $total - $captured),
            'collection_status' => $collection,
            'refund_status' => $refundStatus,
            'legacy_unverified_receipts' => $legacyUnverified,
        ];
    }

    /** Legacy database payment_status is a compatibility projection, not authority. */
    public function legacyStatus(array $state, string $fallback = 'pending'): string
    {
        if ($state['collection_status'] === 'legacy_unverified') {
            return $fallback;
        }
        if (in_array($state['refund_status'], ['partially_refunded', 'refunded'], true)) {
            return $state['refund_status'];
        }
        return $state['collection_status'];
    }
}
