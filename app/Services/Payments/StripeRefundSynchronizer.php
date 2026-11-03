<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\StripeRefundSync;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stripe Dashboard -> verified provider reads -> append/advance local accounting.
 * Does not expose any operation that initiates or modifies a Stripe refund.
 */
final class StripeRefundSynchronizer
{
    public function __construct(
        private readonly StripeRefundProvider $provider,
        private readonly FinancialStateService $financialState,
    ) {
    }

    public function syncRefund(string $id): ?OrderPayment
    {
        if (! preg_match('/^re_[a-zA-Z0-9]{3,180}$/D', $id)) {
            throw new PaymentGatewayException('Invalid Stripe refund reference.');
        }

        // Always read the provider's CURRENT state. Webhooks are not ordered.
        $refund = $this->provider->refund($id);
        if (($refund['id'] ?? null) !== $id) {
            throw new PaymentGatewayException('Stripe refund reference mismatch.');
        }
        $chargeId = (string) ($refund['charge'] ?? '');
        if (! preg_match('/^ch_[a-zA-Z0-9]{3,180}$/D', $chargeId)) {
            $this->recordException($id, (bool) ($refund['livemode'] ?? false), 'missing_charge');
            return null;
        }
        $charge = $this->provider->charge($chargeId);
        if (($charge['id'] ?? null) !== $chargeId || ! isset($refund['livemode'], $charge['livemode'])
            || (bool) $refund['livemode'] !== (bool) $charge['livemode']) {
            throw new PaymentGatewayException('Stripe refund and charge identity conflict.');
        }
        $livemode = (bool) $refund['livemode'];
        $key = (string) config('payments.gateways.stripe.secret_key');
        $liveKey = str_starts_with($key, 'sk_live_') || str_starts_with($key, 'rk_live_');
        $testKey = str_starts_with($key, 'sk_test_') || str_starts_with($key, 'rk_test_');
        if ((! $liveKey && ! $testKey) || $liveKey !== $livemode) {
            throw new PaymentGatewayException('Stripe refund mode differs from the configured account.');
        }
        $intentId = is_string($charge['payment_intent'] ?? null) ? $charge['payment_intent'] : '';
        $status = strtolower((string) ($refund['status'] ?? ''));
        $amount = $refund['amount'] ?? null;
        $currency = strtoupper((string) ($refund['currency'] ?? ''));
        if (! preg_match('/^pi_[a-zA-Z0-9]{3,180}$/D', $intentId)
            || ! in_array($status, ['succeeded', 'pending', 'requires_action', 'failed', 'canceled'], true)
            || ! is_int($amount) || $amount < 1 || $currency === ''
            || $currency !== strtoupper((string) ($charge['currency'] ?? ''))
            || ! ($charge['paid'] ?? false) || ! ($charge['captured'] ?? false)
            || $amount > (int) ($charge['amount'] ?? 0)) {
            $this->recordException($id, $livemode, 'invalid_provider_record');
            return null;
        }
        $matches = OrderPayment::query()->where('provider', 'stripe')
            ->where('provider_payment_id', $intentId)->where('status', 'paid')->limit(2)->get();
        $payment = $matches->count() === 1 ? $matches->first() : null;
        $matchedOrder = $payment ? Order::query()->withTrashed()->find($payment->order_id) : null;
        if (! $payment || ! $matchedOrder || $currency !== strtoupper((string) $payment->currency)
            || $currency !== strtoupper((string) $matchedOrder->currency)
            || $amount > Money::toMinor((string) $payment->amount, $currency)
            || (int) ($charge['amount'] ?? -1) !== Money::toMinor((string) $payment->amount, $currency)
            || (isset($charge['amount_captured'])
                && (int) $charge['amount_captured'] !== Money::toMinor((string) $payment->amount, $currency))) {
            $this->recordException($id, $livemode, 'unmatched_capture');
            return null;
        }

        return DB::transaction(function () use ($payment, $refund, $id, $livemode, $amount, $currency, $status): OrderPayment {
            // Consistent lock order also used by payment confirmation.
            $order = Order::query()->withTrashed()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status !== 'paid') {
                throw new PaymentGatewayException('Refund is not associated with a confirmed receipt.');
            }

            $link = StripeRefundSync::query()->firstOrCreate(
                ['livemode' => $livemode, 'provider_refund_id' => $id],
                ['status' => 'exception', 'exception_code' => 'awaiting_match'],
            );
            $link = StripeRefundSync::query()->lockForUpdate()->findOrFail($link->id);
            $linkedRefund = $link->order_refund_id
                ? OrderRefund::query()->lockForUpdate()->findOrFail($link->order_refund_id)
                : null;
            if ($linkedRefund && ((int) $linkedRefund->order_id !== (int) $order->id
                || (int) $linkedRefund->order_payment_id !== (int) $lockedPayment->id
                || $linkedRefund->provider_reference !== $id
                || Money::toMinor((string) $linkedRefund->amount, $currency) !== $amount)) {
                $link->forceFill(['status' => 'exception', 'exception_code' => 'existing_record_conflict', 'checked_at' => now()])->save();
                return $lockedPayment;
            }

            // Historical or manually entered provider references must not be
            // silently counted a second time in a newly linked Stripe refund.
            if (! $linkedRefund && OrderRefund::query()->where('provider_reference', $id)->exists()) {
                $link->forceFill(['status' => 'exception', 'exception_code' => 'legacy_reference_conflict', 'checked_at' => now()])->save();
                return $lockedPayment;
            }

            $next = match ($status) {
                'succeeded' => 'issued',
                'pending', 'requires_action' => 'processing',
                default => 'failed',
            };
            $previousRefundStatus = $linkedRefund?->status ?? 'none';
            if ($linkedRefund?->status === 'issued' && $next !== 'issued') {
                // A stale/reversed event must never erase a successful receipt.
                $link->forceFill(['status' => 'exception', 'exception_code' => 'terminal_status_conflict', 'checked_at' => now()])->save();
                return $lockedPayment;
            }
            if ($next === 'issued' && $linkedRefund?->status !== 'issued') {
                $already = 0;
                $issued = OrderRefund::query()->where('order_payment_id', $lockedPayment->id)
                    ->where('status', 'issued')->pluck('amount');
                foreach ($issued as $value) {
                    $already += Money::toMinor((string) $value, $currency);
                }
                if ($already + $amount > Money::toMinor((string) $lockedPayment->amount, $currency)) {
                    $link->forceFill(['status' => 'exception', 'exception_code' => 'refund_exceeds_capture', 'checked_at' => now()])->save();
                    return $lockedPayment;
                }
            }
            if (! $linkedRefund) {
                $linkedRefund = OrderRefund::query()->create([
                    'order_id' => $order->id,
                    'order_payment_id' => $lockedPayment->id,
                    'refund_number' => 'RF-'.Str::upper(Str::random(16)),
                    'amount' => Money::fromMinor($amount, $currency),
                    'currency' => $currency,
                    'method' => 'original_payment',
                    'status' => $next,
                    'provider_reference' => $id,
                    'reason' => 'Stripe Dashboard refund',
                    'processed_at' => $next === 'issued' ? now() : null,
                ]);
                $link->order_refund_id = $linkedRefund->id;
                $wasIssued = false;
            } else {
                $wasIssued = $linkedRefund->status === 'issued';
                $linkedRefund->forceFill([
                    'status' => $next,
                    'processed_at' => $next === 'issued' ? ($linkedRefund->processed_at ?: now()) : null,
                ])->save();
            }
            $link->forceFill([
                'status' => $next,
                'exception_code' => null,
                'checked_at' => now(),
            ])->save();
            app(PaymentAuditRecorder::class)->record(
                'stripe:refund:'.hash('sha256', $id).':'.$next,
                'refund_'.($next === 'issued' ? 'confirmed' : $next),
                $order->id, $lockedPayment->id, $linkedRefund->id,
                providerReference: $id,
                oldStatus: $previousRefundStatus,
                newStatus: $next, amountMinor: $amount, currency: $currency,
            );

            if ($next === 'issued' && ! $wasIssued) {
                $this->recordConfirmedRefund($order, $lockedPayment, $linkedRefund);
            }
            return $lockedPayment;
        }, 3);
    }

    public function syncCharge(string $chargeId): void
    {
        if (! preg_match('/^ch_[a-zA-Z0-9]{3,180}$/D', $chargeId)) {
            throw new PaymentGatewayException('Invalid Stripe charge reference.');
        }
        foreach ($this->provider->refundIdsForCharge($chargeId) as $refundId) {
            $this->syncRefund($refundId);
        }
    }

    private function recordConfirmedRefund(Order $order, OrderPayment $payment, OrderRefund $refund): void
    {
        $currency = strtoupper((string) $payment->currency);
        $total = 0;
        foreach (OrderRefund::query()->where('order_payment_id', $payment->id)
            ->where('status', 'issued')->pluck('amount') as $amount) {
            $total += Money::toMinor((string) $amount, $currency);
        }
        $payment->forceFill(['refunded_amount' => Money::fromMinor($total, $currency)])->save();
        $state = $this->financialState->forOrder($order);
        $order->forceFill(['payment_status' => $this->financialState->legacyStatus($state, (string) $order->payment_status)])->save();

        if (! $refund->creditNote()->exists()) {
            $refund->creditNote()->create([
                'order_id' => $order->id,
                'credit_note_number' => 'CN-'.now()->format('ymd').'-'.Str::upper(Str::random(12)),
                'amount' => $refund->amount,
                'currency' => $currency,
                'reason' => $refund->reason,
                'issued_at' => now(),
            ]);
        }
        $order->histories()->create([
            'status' => $order->status,
            'title' => 'Stripe refund confirmed',
            'description' => 'Stripe confirmed refund '.$refund->refund_number.'.',
            'metadata' => ['order_payment_id' => $payment->id, 'order_refund_id' => $refund->id],
            'occurred_at' => now(),
        ]);
    }

    private function recordException(string $id, bool $livemode, string $code): void
    {
        StripeRefundSync::query()->upsert(
            [[
                'livemode' => $livemode, 'provider_refund_id' => $id,
                'status' => 'exception', 'exception_code' => $code,
                'checked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]],
            ['livemode', 'provider_refund_id'],
            ['status', 'exception_code', 'checked_at', 'updated_at'],
        );
        app(PaymentAuditRecorder::class)->record(
            'stripe:refund:'.hash('sha256', $id).':exception:'.$code,
            'refund_exception', null, providerReference: $id,
            newStatus: 'exception',
        );
        // Only a controlled reason code, never raw provider payloads.
        \Illuminate\Support\Facades\Log::warning('Stripe refund reconciliation exception.', [
            'code' => $code,
        ]);
    }
}
