<?php

namespace App\Console\Commands;

use App\Models\OrderPayment;
use App\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--provider=} {--limit=}';

    protected $description = 'Reconcile pending provider payments with the configured payment gateways.';

    public function handle(PaymentOrchestrator $payments, PaymentGatewayManager $gateways): int
    {
        $minutes = max(1, (int) config('payments.reconciliation.pending_after_minutes', 10));
        $limit = max(1, min(500, (int) ($this->option('limit') ?: config('payments.reconciliation.batch_size', 100))));
        $provider = trim((string) $this->option('provider'));

        $cooldown = max(1, (int) config('payments.reconciliation.cooldown_minutes', 20));
        // Rotate previously checked attempts behind unchecked ones, including
        // provider outages; the oldest stuck record must not monopolize batches.
        $query = OrderPayment::query()
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('provider_session_id')
            ->where('attempted_at', '<=', now()->subMinutes($minutes))
            ->where(function ($query) use ($cooldown): void {
                $query->whereNull('last_reconciled_at')
                    ->orWhere('last_reconciled_at', '<=', now()->subMinutes($cooldown));
            })->orderBy('last_reconciled_at')->orderBy('attempted_at');

        if ($provider !== '') {
            $query->where('provider', $provider);
        }

        $count = 0;
        $failed = 0;

        foreach ($query->limit($limit)->get() as $payment) {
            if (! $gateways->isAvailable((string) ($payment->gateway ?: $payment->provider))) {
                // An unconfigured provider must not permanently starve later
                // eligible payment attempts in the bounded reconciliation batch.
                DB::table('order_payments')->where('id', $payment->id)->update([
                    'last_reconciled_at' => now(),
                    'reconciliation_failures' => DB::raw('reconciliation_failures + 1'),
                ]);
                Log::warning('Payment reconciliation provider unavailable.', ['payment_id' => $payment->id]);
                $failed++;
                continue;
            }

            try {
                $payments->reconcile($payment);
                DB::table('order_payments')->where('id', $payment->id)->update([
                    'last_reconciled_at' => now(), 'reconciliation_failures' => 0,
                ]);
                $count++;
            } catch (Throwable $exception) {
                // No payment data or Stripe exception text in operational logs.
                Log::warning('Payment reconciliation requires retry.', [
                    'payment_id' => $payment->id, 'type' => class_basename($exception),
                ]);
                DB::table('order_payments')->where('id', $payment->id)->update([
                    'last_reconciled_at' => now(),
                    'reconciliation_failures' => DB::raw('reconciliation_failures + 1'),
                ]);
                $failed++;
            }
        }

        $this->info("Reconciled {$count} payment(s); {$failed} failed and can be retried safely.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
