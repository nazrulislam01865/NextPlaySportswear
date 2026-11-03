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
use Illuminate\Support\Facades\Log;
use App\Payments\Exceptions\PaymentGatewayException;
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

            // Never steal a live worker's claim, even on a retry. Stale claims
            // are recovered by the scheduler after the configured timeout.
            if ($alreadyClaimed) {
                return null;
            }

            $locked->update([
                'status' => 'processing', 'last_error' => null,
                'last_attempted_at' => now(),
                'attempt_count' => min(PHP_INT_MAX, (int) $locked->attempt_count + 1),
            ]);

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
                'last_error' => class_basename($exception),
            ])->save();

            Log::error('Payment webhook processing failed.', [
                'event_id' => $this->webhookId, 'type' => class_basename($exception),
            ]);
            // Queue failure records must not retain gateway messages or card details.
            throw new PaymentGatewayException('Verified payment event requires retry or reconciliation.');
        }
    }
}
