<?php

namespace App\Console\Commands;

use App\Models\OrderPayment;
use App\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Console\Command;
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

        $query = OrderPayment::query()
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('provider_session_id')
            ->where('attempted_at', '<=', now()->subMinutes($minutes))
            ->oldest('attempted_at');

        if ($provider !== '') {
            $query->where('provider', $provider);
        }

        $count = 0;
        $failed = 0;

        foreach ($query->limit($limit)->get() as $payment) {
            if (! $gateways->isAvailable((string) ($payment->gateway ?: $payment->provider))) {
                continue;
            }

            try {
                $payments->reconcile($payment);
                $count++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $this->info("Reconciled {$count} payment(s); {$failed} failed and can be retried safely.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
