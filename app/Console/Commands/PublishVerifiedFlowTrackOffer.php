<?php

namespace App\Console\Commands;

use App\Models\BulkQuoteRequest;
use App\Services\BulkQuote\UnifiedQuotePublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** One-time recovery for an offer verified before unified publication was deployed. */
final class PublishVerifiedFlowTrackOffer extends Command
{
    protected $signature = 'flowtrack:publish-verified-offer
        {quote : NextPlay quotation reference or database ID}
        {--check : Inspect readiness without publishing anything}';

    protected $description = 'Publish one previously verified FlowTracker offer using the normal, idempotent NextPlay quotation publication service.';

    public function handle(UnifiedQuotePublisher $publisher): int
    {
        $value = trim((string) $this->argument('quote'));
        $quote = ctype_digit($value)
            ? BulkQuoteRequest::query()->whereKey((int) $value)->first()
            : BulkQuoteRequest::query()->where('reference', $value)->first();

        if (! $quote) {
            $this->error('Quotation not found.');
            return self::FAILURE;
        }

        $this->line('NextPlay quotation: '.$quote->reference);
        $this->line('FlowTracker inquiry: '.($quote->flowtrack_inquiry_id ?: 'not linked'));

        if ($quote->quotation_version > 0 && $quote->quotation_sent_at !== null) {
            $this->info('Already formally published as quotation version '.$quote->quotation_version.'. No changes made.');
            return self::SUCCESS;
        }
        if ($quote->quotation_version > 0 || $quote->quotation_sent_at !== null) {
            $this->error('Inconsistent quotation publication state. Review the record before proceeding.');
            return self::FAILURE;
        }
        if (! $quote->flowtrack_inquiry_id || in_array($quote->status, [
            BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED,
            BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED,
        ], true)) {
            $this->error('Quotation is unlinked or finalized. No changes made.');
            return self::FAILURE;
        }

        $hash = (string) $quote->flowtrack_offer_manifest_hash;
        if (! preg_match('/^[a-f0-9]{64}$/i', $hash)
            || ! hash_equals($hash, (string) $quote->flowtrack_offer_ready_hash)
            || (int) $quote->flowtrack_offer_file_count < 1) {
            $this->error('Offer transfer is not fully verified. Do not publish until the transfer finishes.');
            return self::FAILURE;
        }

        $files = $quote->quoteFiles()->where('category', 'flowtrack_offer')->get(['id', 'path']);
        if ($files->count() !== (int) $quote->flowtrack_offer_file_count
            || $files->contains(fn ($file) => ! Storage::disk('local')->exists($file->path))) {
            $this->error('One or more verified offer files are missing from private storage.');
            return self::FAILURE;
        }

        $this->info('Verified offer ready: '.$files->count().' private file(s).');
        if ($this->option('check')) {
            $this->line('Read-only check passed. Run without --check to publish through the existing workflow.');
            return self::SUCCESS;
        }

        try {
            $published = $publisher->fromFlowTrack((int) $quote->id, $hash);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Publication failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $quote->refresh();
        if ($published || ($quote->quotation_version > 0 && $quote->quotation_sent_at !== null)) {
            $this->info('Formal quotation ready: version '.$quote->quotation_version.'. The customer review and feedback flow is now available.');
            return self::SUCCESS;
        }

        $this->error('Publication was not completed. Check the Laravel logs.');
        return self::FAILURE;
    }
}
