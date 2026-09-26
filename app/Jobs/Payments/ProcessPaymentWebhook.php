<?php

namespace App\Jobs\Payments;

use App\Models\PaymentWebhookEvent;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessPaymentWebhook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [5, 20, 60, 180, 600];

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $webhookId)
    {
    }

    public function uniqueId(): string
    {
        return 'payment-webhook:'.$this->webhookId;
    }

    public function handle(PaymentOrchestrator $payments): void
    {
        $event = DB::transaction(function (): ?PaymentWebhookEvent {
            $locked = PaymentWebhookEvent::query()->lockForUpdate()->find($this->webhookId);

            if (! $locked || $locked->status === 'processed') {
                return null;
            }

            $claimTimeout = max(1, (int) config('payments.webhooks.claim_timeout_minutes', 10));
            $alreadyClaimed = $locked->status === 'processing'
                && $locked->updated_at?->isAfter(now()->subMinutes($claimTimeout));

            // A separately-dispatched duplicate job should not process an
            // event already owned by another worker. A real queue retry has
            // attempts() > 1 and is allowed to recover a job after a worker
            // crash or timeout.
            if ($alreadyClaimed && $this->attempts() <= 1) {
                return null;
            }

            $locked->update(['status' => 'processing', 'last_error' => null]);

            return $locked->fresh();
        });

        if (! $event) {
            return;
        }

        try {
            $payments->processWebhook($event);
            $event->forceFill([
                'status' => 'processed',
                'processed_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $event->forceFill([
                'status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();

            throw $exception;
        }
    }
}
