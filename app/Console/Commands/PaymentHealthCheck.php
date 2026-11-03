<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentHealthSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Read-only health and optional operator-only alerting. */
final class PaymentHealthCheck extends Command
{
    protected $signature = 'payments:health {--json : Print metric values without customer details}';

    protected $description = 'Report webhook, reconciliation, refund and recent financial consistency signals.';

    public function handle(PaymentHealthSnapshot $snapshot): int
    {
        $metrics = $snapshot->collect();
        $alarm = $metrics['stale_webhooks'] > 0
            || $metrics['failed_webhooks'] > 0
            || $metrics['exhausted_webhooks'] > 0
            || $metrics['refund_exceptions'] > 0
            || $metrics['old_unsettled_attempts'] > (int) config('payments.monitoring.unsettled_warning_count', 20)
            || $metrics['failed_payment_queue_jobs'] > 0
            || $metrics['out_of_balance_sample'] > 0;
        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_THROW_ON_ERROR));
        } else {
            foreach ($metrics as $key => $value) {
                $this->line($key.': '.$value);
            }
        }
        if (! $alarm) {
            return self::SUCCESS;
        }
        Log::warning('NextPlay payment operations require review.', $metrics);
        $email = trim((string) config('payments.monitoring.alert_email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                // One alert per cooldown window, even with multiple app servers.
                $minutes = max(15, (int) config('payments.monitoring.alert_cooldown_minutes', 60));
                if (Cache::add('nextplay:payment-health:alert', true, now()->addMinutes($minutes))) {
                    Mail::raw(
                        "NextPlay payment monitoring detected unresolved events.\n\n"
                        .collect($metrics)->map(fn ($value, $key) => $key.': '.$value)->implode("\n")
                        ."\n\nInspect the payment queue and run read-only reconciliation."
                        , function ($mail) use ($email): void {
                            $mail->to($email)->subject('NextPlay payment reconciliation warning');
                        },
                    );
                }
            } catch (Throwable $exception) {
                Log::error('Payment health operator notification failed.', [
                    'type' => class_basename($exception),
                ]);
            }
        }
        return self::FAILURE;
    }
}
