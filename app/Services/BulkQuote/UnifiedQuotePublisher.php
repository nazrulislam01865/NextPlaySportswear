<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteRequest;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Single versioning/publication boundary used by FlowTracker and NextPlay Admin. */
final class UnifiedQuotePublisher
{
    public function __construct(
        private readonly BulkQuoteGuestService $files,
        private readonly TransactionalEmailManager $emails,
    ) {}

    /**
     * Publish an entire verified FlowTracker offer as a formal quotation. No
     * price is inferred from a PDF: a file-only quotation is explicitly unpriced.
     * Concurrent retries of one manifest cannot create another version.
     */
    public function fromFlowTrack(int $quoteId, string $manifestHash): bool
    {
        $created = [];
        try {
            $published = DB::transaction(function () use ($quoteId, $manifestHash, &$created): bool {
                $quote = BulkQuoteRequest::query()->whereKey($quoteId)->lockForUpdate()->firstOrFail();
                if (! filled($manifestHash) || ! hash_equals((string) $quote->flowtrack_offer_manifest_hash, $manifestHash)
                    || ! hash_equals((string) $quote->flowtrack_offer_ready_hash, $manifestHash)) return false;
                if ($this->isFinalized($quote)) return false;
                $details = (array) ($quote->quotation_details ?: []);
                if (($details['_flowtrack_published_manifest'] ?? null) === $manifestHash) return false;
                // An Admin-published version is already the completed single
                // process. Receiving its FlowTracker callback must be a no-op.
                if (($details['_publication_origin'] ?? null) === 'nextplay_admin') return false;
                $offerFiles = $quote->quoteFiles()->where('category', 'flowtrack_offer')->orderBy('id')->get();
                if ($offerFiles->isEmpty() || $offerFiles->count() !== (int) $quote->flowtrack_offer_file_count) {
                    throw ValidationException::withMessages(['files' => 'The complete FlowTracker offer has not been verified.']);
                }
                $version = (int) $quote->quotation_version + 1;
                foreach ($offerFiles as $offerFile) {
                    $created[] = $this->copyExisting($quote, $offerFile, $version, null);
                }
                // Do not reuse obsolete prices from an earlier version or guess
                // them from an uploaded quotation document.
                $details = [
                    '_publication_origin' => 'flowtrack',
                    '_flowtrack_published_manifest' => $manifestHash,
                    'pricing_pending' => true,
                    'payment_terms' => 'See attached quotation document',
                    'quotation_notes' => '',
                ];
                $this->finish($quote, $version, $details, null, null, null, count($created));
                return true;
            });
        } catch (\Throwable $exception) {
            $this->files->deleteFiles($created);
            throw $exception;
        }
        if ($published) $this->notifyCustomer($quoteId);
        return $published;
    }

    /** NextPlay Admin completes the SAME publication action, with structured prices. */
    public function fromAdmin(int $quoteId, array $data, array $uploads, int $actorId): void
    {
        $created = [];
        try {
            DB::transaction(function () use ($quoteId, $data, $uploads, $actorId, &$created): void {
                $quote = BulkQuoteRequest::query()->whereKey($quoteId)->lockForUpdate()->firstOrFail();
                $detailsBefore = (array) ($quote->quotation_details ?: []);
                $amendingUnpricedAcceptance = $quote->status === BulkQuoteRequest::STATUS_ACCEPTED
                    && $quote->guest_decision === 'accept_only'
                    && ($detailsBefore['pricing_pending'] ?? false)
                    && $quote->converted_order_id === null;
                if ($this->isFinalized($quote) && ! $amendingUnpricedAcceptance) {
                    throw ValidationException::withMessages(['quote' => 'This quotation is finalized and cannot be republished.']);
                }
                $total = round((float) $data['subtotal'] + (float) $data['customization'] + (float) $data['shipping'], 2);
                if ($total <= 0 || $total > 9999999999.99) {
                    throw ValidationException::withMessages(['subtotal' => 'The quotation total must be greater than zero.']);
                }
                $useOffer = (bool) ($data['use_flowtrack_offer'] ?? false);
                if ($useOffer && (! filled($quote->flowtrack_offer_manifest_hash)
                    || ! hash_equals((string) $quote->flowtrack_offer_manifest_hash, (string) $quote->flowtrack_offer_ready_hash))) {
                    throw ValidationException::withMessages(['files' => 'FlowTracker files are not fully transferred.']);
                }
                $offerFiles = $useOffer ? $quote->quoteFiles()->where('category', 'flowtrack_offer')->orderBy('id')->get() : collect();
                if (count($uploads) + $offerFiles->count() < 1 || count($uploads) + $offerFiles->count() > 8) {
                    throw ValidationException::withMessages(['files' => 'Select 1–8 quotation files.']);
                }
                $version = (int) $quote->quotation_version + 1;
                foreach ($offerFiles as $file) $created[] = $this->copyExisting($quote, $file, $version, $actorId);
                foreach ($uploads as $upload) {
                    $created[] = $this->files->storeFile($quote, $upload, 'quotation', $version, $actorId);
                }
                $details = [
                    'quantity' => (int) $data['quantity'],
                    'subtotal' => round((float) $data['subtotal'], 2),
                    'customization' => round((float) $data['customization'], 2),
                    'shipping' => round((float) $data['shipping'], 2),
                    'shipping_method' => $data['shipping_method'] ?? '',
                    'payment_terms' => $data['payment_terms'],
                    'quotation_notes' => $data['quotation_notes'] ?? '',
                    '_publication_origin' => 'nextplay_admin',
                    'pricing_pending' => false,
                ];
                $this->finish($quote, $version, $details, $total, $data['valid_until'], $actorId, count($created));
            });
        } catch (\Throwable $exception) {
            $this->files->deleteFiles($created);
            throw $exception;
        }
        $this->notifyCustomer($quoteId);
        $quote = BulkQuoteRequest::query()->findOrFail($quoteId);
        if ($quote->flowtrack_inquiry_id) {
            \App\Jobs\Integrations\SyncPublishedQuoteToFlowTrack::dispatch($quoteId, (int) $quote->quotation_version)
                ->onConnection((string) config('flowtrack.queue.connection', 'database'))
                ->onQueue((string) config('flowtrack.queue.name', 'integrations'));
        }
    }

    private function copyExisting(BulkQuoteRequest $quote, $source, int $version, ?int $actorId)
    {
        $disk = Storage::disk('local');
        $name = basename(str_replace('\\', '/', (string) $source->original_name));
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (! in_array($ext, ['pdf', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'], true)
            || $source->size < 1 || $source->size > 10485760 || ! $disk->exists($source->path)) {
            throw ValidationException::withMessages(['files' => 'A quotation file is missing or invalid.']);
        }
        $path = 'bulk-quote-private/'.$quote->id.'/quotation/'.Str::random(36).'-'.$name;
        if (! $disk->copy($source->path, $path)) {
            throw ValidationException::withMessages(['files' => 'The quotation file could not be copied.']);
        }
        try {
            return $quote->quoteFiles()->create([
                'bulk_quote_message_id' => null, 'uploaded_by' => $actorId,
                'version' => $version, 'category' => 'quotation',
                'source_key' => null, 'path' => $path,
                'original_name' => $name, 'mime_type' => $source->mime_type,
                'size' => $disk->size($path),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }

    private function finish(BulkQuoteRequest $quote, int $version, array $details, ?float $amount, ?string $validUntil, ?int $actorId, int $fileCount): void
    {
        $oldStatus = (string) $quote->status;
        $quote->forceFill([
            'quotation_version' => $version, 'quotation_details' => $details,
            'quoted_amount' => $amount, 'quote_currency' => 'USD',
            'quotation_valid_until' => $validUntil, 'quotation_sent_at' => now(),
            'guest_decision' => null, 'status' => BulkQuoteRequest::STATUS_QUOTATION_SENT,
            'last_contacted_at' => now(),
        ])->saveQuietly(); // do not trigger reverse bulk-quote field sync loops
        $quote->activities()->create([
            'actor_id' => $actorId, 'action' => 'quotation_published',
            'from_status' => $oldStatus, 'to_status' => BulkQuoteRequest::STATUS_QUOTATION_SENT,
            'note' => 'Quotation version '.$version.' published with '.$fileCount.' file(s).',
            'occurred_at' => now(),
        ]);
    }

    private function isFinalized(BulkQuoteRequest $quote): bool
    {
        return in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED, BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED], true);
    }

    private function notifyCustomer(int $quoteId): void
    {
        try {
            $this->emails->bulkQuoteUpdate(BulkQuoteRequest::query()->findOrFail($quoteId),
                'Your quotation is ready', 'Your custom quotation is available for review.');
        } catch (\Throwable $e) { report($e); }
    }
}
