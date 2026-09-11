<?php

namespace App\Console\Commands;

use App\Models\BulkQuoteRequest;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use Illuminate\Console\Command;
use Throwable;

final class FlowTrackSyncInquiry extends Command
{
    protected $signature = 'flowtrack:sync-inquiry {quote : NextPlay bulk quote reference or numeric database ID}';

    protected $description = 'Immediately send one existing NextPlay bulk quote request to FlowTrack as an Inquiry.';

    public function handle(FlowTrackInquirySyncManager $sync): int
    {
        $value = trim((string) $this->argument('quote'));
        $quote = ctype_digit($value)
            ? BulkQuoteRequest::query()->whereKey((int) $value)->first()
            : BulkQuoteRequest::query()->where('reference', $value)->first();

        if (! $quote instanceof BulkQuoteRequest) {
            $this->error('NextPlay bulk quote request not found: '.$value);
            return self::FAILURE;
        }

        try {
            $response = $sync->syncNow($quote);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $quote->refresh();
        $this->info((string) ($response['message'] ?? 'Bulk quote accepted by FlowTrack.'));
        $this->line('NextPlay quote: '.$quote->reference);
        $this->line('FlowTrack Inquiry ID: '.(string) ($quote->flowtrack_inquiry_id ?? 'n/a'));
        $this->line('FlowTrack Inquiry number: '.(string) ($quote->flowtrack_inquiry_number ?? 'n/a'));

        return self::SUCCESS;
    }
}
