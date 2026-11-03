<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\DB;

/** Append-only, bounded, non-sensitive financial transition facts. */
final class PaymentAuditRecorder
{
    public function record(
        string $key,
        string $eventType,
        ?int $orderId,
        ?int $paymentId = null,
        ?int $refundId = null,
        ?string $providerReference = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?int $amountMinor = null,
        ?string $currency = null,
        ?bool $livemode = null,
    ): void {
        if (! preg_match('/^[a-zA-Z0-9:._-]{1,190}$/D', $key)
            || ! preg_match('/^[a-z_]{1,60}$/D', $eventType)) {
            throw new \InvalidArgumentException('Invalid payment audit identity.');
        }
        // Never put customer content, exception text or sensitive Stripe payloads into the audit log.
        $reference = is_string($providerReference)
            && preg_match('/^(?:pi_|cs_|re_|ch_)[a-zA-Z0-9_]{3,180}$/D', $providerReference)
            ? $providerReference : null;
        $configuredKey = (string) config('payments.gateways.stripe.secret_key');
        $mode = $livemode ?? match (true) {
            str_starts_with($configuredKey, 'sk_live_'), str_starts_with($configuredKey, 'rk_live_') => true,
            str_starts_with($configuredKey, 'sk_test_'), str_starts_with($configuredKey, 'rk_test_') => false,
            default => null,
        };
        DB::table('payment_audit_entries')->insertOrIgnore([
            'event_key' => $key,
            'event_type' => $eventType,
            'source' => 'stripe_verified',
            'livemode' => $mode,
            'order_id' => $orderId,
            'order_payment_id' => $paymentId,
            'order_refund_id' => $refundId,
            'provider_reference' => $reference,
            'old_status' => $oldStatus ? substr($oldStatus, 0, 40) : null,
            'new_status' => $newStatus ? substr($newStatus, 0, 40) : null,
            'amount_minor' => $amountMinor,
            'currency' => $currency ? strtoupper(substr($currency, 0, 3)) : null,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }
}
