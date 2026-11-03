<?php

namespace App\Console\Commands;

use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Bounded queue recovery for verified events lost during worker/queue outages. */
final class RecoverPaymentWebhooks extends Command
{
    protected $signature = 'payments:recover-webhooks {--limit=100}';

    protected $description = 'Re-enqueue stale verified payment events; never manufacture payment state.';

    public function handle(): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        $cooldown = max(1, (int) config('payments.webhooks.redispatch_after_minutes', 5));
        $timeout = max(2, (int) config('payments.webhooks.claim_timeout_minutes', 10));
        $max = max(1, (int) config('payments.webhooks.max_recovery_attempts', 30));

        $events = PaymentWebhookEvent::query()->where('provider', 'stripe')
            ->where('attempt_count', '<', $max)
            ->where(function ($query) use ($cooldown): void {
                $query->whereNull('last_dispatched_at')
                    ->orWhere('last_dispatched_at', '<=', now()->subMinutes($cooldown));
            })
            ->where(function ($query) use ($timeout): void {
                $query->where(function ($q): void {
                    $q->where('status', 'received')->where('received_at', '<=', now()->subMinutes(2));
                })->orWhere(function ($q) use ($timeout): void {
                    // Include processing rows created before Phase 6 added
                    // last_attempted_at, using their last update as the claim age.
                    $q->where('status', 'processing')->where(function ($claim) use ($timeout): void {
                        $claim->where('last_attempted_at', '<=', now()->subMinutes($timeout))
                            ->orWhere(function ($legacy) use ($timeout): void {
                                $legacy->whereNull('last_attempted_at')
                                    ->where('updated_at', '<=', now()->subMinutes($timeout));
                            });
                    });
                })->orWhere(function ($q): void {
                    $q->where('status', 'failed')->where('updated_at', '<=', now()->subMinutes(2));
                });
            })
            ->orderBy('last_dispatched_at')->orderBy('id')->limit($limit)->get(['id']);

        $queued = 0;
        $errors = 0;
        foreach ($events as $event) {
            // Concurrent scheduler instances may race; only one may claim a
            // re-dispatch in the cooldown window. The queue job rechecks state.
            $claimed = DB::table('payment_webhook_events')->where('id', $event->id)
                ->whereIn('status', ['received', 'failed', 'processing'])
                ->where(function ($query) use ($cooldown): void {
                    $query->whereNull('last_dispatched_at')
                        ->orWhere('last_dispatched_at', '<=', now()->subMinutes($cooldown));
                })
                ->update(['last_dispatched_at' => now()]);
            if (! $claimed) {
                continue;
            }
            try {
                ProcessPaymentWebhook::dispatch((int) $event->id)
                    ->onQueue((string) config('payments.webhooks.queue', 'payments'));
                $queued++;
            } catch (Throwable $exception) {
                Log::error('Payment webhook recovery enqueue failed.', [
                    'event_id' => $event->id, 'type' => class_basename($exception),
                ]);
                $errors++;
            }
        }
        $this->info("Payment webhook recovery: {$queued} requeued, {$errors} enqueue failures.");
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
