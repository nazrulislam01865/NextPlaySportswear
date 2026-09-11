<?php

namespace App\Jobs\Integrations;

use App\Models\BulkQuoteRequest;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncBulkQuoteToFlowTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(public readonly int $bulkQuoteId)
    {
        $this->tries = max(1, (int) config('flowtrack.queue.tries', 5));
        $this->timeout = max(10, (int) config('flowtrack.queue.timeout', 30));
    }

    public function handle(FlowTrackInquirySyncManager $sync): void
    {
        $quote = BulkQuoteRequest::query()->find($this->bulkQuoteId);

        if (! $quote instanceof BulkQuoteRequest) {
            Log::warning('flowtrack.inquiry_sync_skipped_missing_quote', ['bulk_quote_id' => $this->bulkQuoteId]);
            return;
        }

        $sync->syncNow($quote, false);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return (array) config('flowtrack.queue.backoff', [10, 60, 300, 900, 1800]);
    }

    public function failed(Throwable $exception): void
    {
        app(FlowTrackInquirySyncManager::class)->recordPermanentFailure($this->bulkQuoteId, $exception);
    }
}
