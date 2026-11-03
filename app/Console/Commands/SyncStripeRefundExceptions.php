<?php

namespace App\Console\Commands;

use App\Models\StripeRefundSync;
use App\Services\Payments\StripeRefundSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Manual read-only Stripe verification of unmatched/contradictory refund events. */
final class SyncStripeRefundExceptions extends Command
{
    protected $signature = 'payments:sync-refund-exceptions {--limit=50}';

    protected $description = 'Recheck Stripe Dashboard refund exceptions without initiating any refund.';

    public function handle(StripeRefundSynchronizer $refunds): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $records = StripeRefundSync::query()->where('status', 'exception')
            ->orderBy('checked_at')->limit($limit)->get();
        $resolved = 0;
        $failed = 0;
        foreach ($records as $record) {
            try {
                $refunds->syncRefund((string) $record->provider_refund_id);
                if ($record->fresh()?->status !== 'exception') {
                    $resolved++;
                }
            } catch (Throwable $exception) {
                // Move a provider-outage exception to the back of the batch.
                $record->forceFill(['checked_at' => now()])->save();
                Log::warning('Stripe refund exception retry failed.', [
                    'refund_sync_id' => $record->id, 'type' => class_basename($exception),
                ]);
                $failed++;
            }
        }
        $this->info("Rechecked {$records->count()} refund exception(s); {$resolved} resolved; {$failed} provider error(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
