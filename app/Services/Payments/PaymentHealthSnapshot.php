<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentWebhookEvent;
use App\Models\StripeRefundSync;
use App\Payments\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Bounded operational signals. No remote provider calls or customer data. */
final class PaymentHealthSnapshot
{
    public function collect(): array
    {
        $stale = max(5, (int) config('payments.monitoring.stale_after_minutes', 30));
        $maxRecovery = max(1, (int) config('payments.webhooks.max_recovery_attempts', 30));
        $pending = PaymentWebhookEvent::query()->whereIn('status', ['received', 'processing'])
            ->where('received_at', '<=', now()->subMinutes($stale))->count();
        $failed = PaymentWebhookEvent::query()->where('status', 'failed')->count();
        $exhausted = PaymentWebhookEvent::query()->where('status', 'failed')
            ->where('attempt_count', '>=', $maxRecovery)->count();
        $exceptions = StripeRefundSync::query()->where('status', 'exception')->count();
        $unsettled = OrderPayment::query()->where('provider', 'stripe')
            ->whereIn('status', ['pending', 'processing'])
            ->where('attempted_at', '<=', now()->subMinutes($stale))->count();

        $failedJobs = 0;
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->where('queue', (string) config('payments.webhooks.queue', 'payments'))->count();
        }

        // A bounded sample, not a costly all-orders table scan or N+1 queries.
        $sampleSize = max(1, min(200, (int) config('payments.monitoring.sample_size', 75)));
        $orderIds = OrderPayment::query()->where('provider', 'stripe')->where('status', 'paid')
            ->latest('id')->limit($sampleSize)->pluck('order_id')->unique()->values();
        $outOfBalance = 0;
        if ($orderIds->isNotEmpty()) {
            $orders = Order::query()->withTrashed()->whereIn('id', $orderIds)
                ->with(['payments', 'refunds'])->get();
            foreach ($orders as $order) {
                try {
                    $currency = strtoupper((string) $order->currency);
                    $total = Money::toMinor((string) $order->grand_total, $currency);
                    $captured = 0;
                    $refunded = 0;
                    foreach ($order->payments as $payment) {
                        if ($payment->status === 'paid') {
                            if (strtoupper((string) $payment->currency) !== $currency) {
                                throw new \UnexpectedValueException('Currency mismatch');
                            }
                            $captured += Money::toMinor((string) $payment->amount, $currency);
                        }
                    }
                    foreach ($order->refunds as $refund) {
                        if ($refund->status === 'issued') {
                            if (strtoupper((string) $refund->currency) !== $currency) {
                                throw new \UnexpectedValueException('Currency mismatch');
                            }
                            $refunded += Money::toMinor((string) $refund->amount, $currency);
                        }
                    }
                    $recordedStatus = (string) $order->payment_status;
                    if ($captured > $total || $refunded > $captured
                        || ($captured < $total && $recordedStatus === 'paid')
                        || ($total > 0 && $captured >= $total && ! in_array($recordedStatus,
                            ['paid', 'partially_refunded', 'refunded'], true))) {
                        $outOfBalance++;
                    }
                } catch (Throwable) {
                    $outOfBalance++;
                }
            }
        }

        return [
            'stale_webhooks' => $pending,
            'failed_webhooks' => $failed,
            'exhausted_webhooks' => $exhausted,
            'refund_exceptions' => $exceptions,
            'old_unsettled_attempts' => $unsettled,
            'failed_payment_queue_jobs' => $failedJobs,
            'out_of_balance_sample' => $outOfBalance,
            'sampled_orders' => $orderIds->count(),
        ];
    }
}
