<?php

namespace App\Jobs\Integrations;

use App\Models\BulkQuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use RuntimeException;

/** Copy verified offer files privately, then publish through the shared quotation service. */
final class TransferFlowTrackQuoteOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;
    public int $timeout = 120;

    public function __construct(
        public readonly int $quoteId,
        public readonly int $inquiryId,
        public readonly int $taskId,
        public readonly array $files,
        public readonly string $manifestHash,
    ) {}

    public function backoff(): array { return [2, 8, 20, 60, 120, 300]; }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('nextplay-offer-'.$this->quoteId))->releaseAfter(3)->expireAfter(145)];
    }

    public function handle(): void
    {
        $quote = BulkQuoteRequest::query()->find($this->quoteId);
        if (! $quote || (int) $quote->flowtrack_inquiry_id !== $this->inquiryId
            || ! hash_equals((string) $quote->flowtrack_offer_manifest_hash, $this->manifestHash)) return;

        $base = rtrim((string) config('flowtrack.base_url'), '/');
        $token = trim((string) config('flowtrack.token'));
        if ($base === '' || $token === '') throw new RuntimeException('FlowTracker quotation file transfer is not configured.');
        if (app()->environment('production') && ! str_starts_with($base, 'https://')) {
            throw new RuntimeException('FlowTracker offer transfer requires HTTPS in production.');
        }

        $validKeys = [];
        foreach ($this->files as $file) {
            $docId = (int) $file['id'];
            $path = '/api/integrations/nextplay/bulk-orders/'.$this->inquiryId.'/offer-tasks/'.$this->taskId.'/files/'.$docId;
            if (! hash_equals($path, (string) $file['download_path'])) throw new RuntimeException('Invalid private offer file path.');
            $sourceKey = 'flowtrack-offer:'.$this->inquiryId.':'.$this->taskId.':'.$docId;
            $validKeys[] = $sourceKey;
            $existing = $quote->quoteFiles()->where('source_key', $sourceKey)->first();
            if ($existing && Storage::disk('local')->exists($existing->path)
                && (int) Storage::disk('local')->size($existing->path) === (int) $file['size']
                && hash_equals(strtolower((string) $file['checksum_sha256']),
                    hash_file('sha256', Storage::disk('local')->path($existing->path)))) {
                continue;
            }
            // Keep the old record until its replacement is downloaded, verified,
            // and safely stored. Never count a missing/corrupt file as delivered.

            $temp = tempnam(sys_get_temp_dir(), 'nps-offer-');
            if ($temp === false) throw new RuntimeException('Unable to allocate temporary quotation file.');
            $target = null;
            try {
                $response = Http::withToken($token)->withOptions(['allow_redirects' => false])
                    ->connectTimeout(3)->timeout(80)->sink($temp)->get($base.$path);
                if (! $response->successful()) throw new RuntimeException('FlowTracker offer download returned HTTP '.$response->status());
                $size = filesize($temp);
                if ($size === false || $size < 1 || $size > 10485760 || $size !== (int) $file['size']) {
                    throw new RuntimeException('Offer file size does not match the source manifest.');
                }
                if (! hash_equals(strtolower((string) $file['checksum_sha256']), hash_file('sha256', $temp))) {
                    throw new RuntimeException('Offer file failed SHA-256 verification.');
                }
                $name = mb_substr(basename(str_replace('\\', '/', $file['name'])), 0, 190);
                $target = 'bulk-quote-private/'.$quote->id.'/flowtrack-offers/'.bin2hex(random_bytes(16)).'-'.$name;
                $stream = fopen($temp, 'rb');
                if (! is_resource($stream)) throw new RuntimeException('Offer file cannot be read.');
                try { $written = Storage::disk('local')->writeStream($target, $stream); }
                finally { fclose($stream); }
                if (! $written) throw new RuntimeException('Offer file cannot be stored privately.');

                // Check again: a newer completion/reopening may have superseded this manifest.
                $fresh = BulkQuoteRequest::query()->find($this->quoteId);
                if (! $fresh || ! hash_equals((string) $fresh->flowtrack_offer_manifest_hash, $this->manifestHash)) {
                    Storage::disk('local')->delete($target);
                    return;
                }
                $record = $fresh->quoteFiles()->where('source_key', $sourceKey)->first();
                $oldPath = $record?->path;
                if ($record) {
                    $record->forceFill([
                        'path' => $target, 'original_name' => $name,
                        'mime_type' => (string) ($file['mime_type'] ?: 'application/octet-stream'),
                        'size' => $size,
                    ])->save();
                } else {
                    $fresh->quoteFiles()->create([
                        'source_key' => $sourceKey,
                        'category' => 'flowtrack_offer', 'version' => 0,
                        'bulk_quote_message_id' => null, 'uploaded_by' => null,
                        'path' => $target, 'original_name' => $name,
                        'mime_type' => (string) ($file['mime_type'] ?: 'application/octet-stream'),
                        'size' => $size,
                    ]);
                }
                if ($oldPath && $oldPath !== $target) Storage::disk('local')->delete($oldPath);
            } catch (\Throwable $e) {
                if ($target !== null && Storage::disk('local')->exists($target)
                    && ! $quote->quoteFiles()->where('path', $target)->exists()) Storage::disk('local')->delete($target);
                throw $e;
            } finally { @unlink($temp); }
        }

        $fresh = BulkQuoteRequest::query()->find($this->quoteId);
        if (! $fresh || ! hash_equals((string) $fresh->flowtrack_offer_manifest_hash, $this->manifestHash)) return;
        $stale = $fresh->quoteFiles()->where('category', 'flowtrack_offer')
            ->where('source_key', 'like', 'flowtrack-offer:'.$this->inquiryId.':%')
            ->when($validKeys !== [], fn ($query) => $query->whereNotIn('source_key', $validKeys))->get();
        foreach ($stale as $file) {
            $file->delete();
            Storage::disk('local')->delete($file->path);
        }
        // Do not mark a stale or incomplete private transfer as ready.
        $latest = BulkQuoteRequest::query()->whereKey($this->quoteId)->first();
        if (! $latest || ! hash_equals((string) $latest->flowtrack_offer_manifest_hash, $this->manifestHash)) return;

        if ($this->files === []) {
            // Reopened tasks withdraw unpublished staging; already published
            // quotation versions remain part of immutable customer history.
            $latest->forceFill(['flowtrack_offer_ready_hash' => null])->saveQuietly();
            return;
        }
        $verified = $latest->quoteFiles()->where('category', 'flowtrack_offer')
            ->whereIn('source_key', $validKeys)->get();
        if ($verified->count() !== count($validKeys)
            || $verified->contains(fn ($file) => ! Storage::disk('local')->exists($file->path)
                || (int) Storage::disk('local')->size($file->path) !== (int) $file->size)) {
            throw new RuntimeException('Offer synchronization stopped: at least one private file is missing.');
        }

        $latest->forceFill(['flowtrack_offer_ready_hash' => $this->manifestHash])->saveQuietly();
        // A completed Send Offer task IS the publication action. Never report
        // queue success for a merely staged, unpublished quotation.
        $published = app(\App\Services\BulkQuote\UnifiedQuotePublisher::class)
            ->fromFlowTrack($this->quoteId, $this->manifestHash);
        $result = BulkQuoteRequest::query()->findOrFail($this->quoteId);
        $formallyPublished = (int) $result->quotation_version > 0 && $result->quotation_sent_at !== null;
        if (! $published && ! $formallyPublished
            && ! in_array($result->status, [BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED], true)) {
            throw new RuntimeException('Offer files transferred, but formal quotation publication did not complete.');
        }
        Log::info('flowtrack.quotation_publication_processed', [
            'quote_id' => $this->quoteId,
            'inquiry_id' => $this->inquiryId,
            'task_id' => $this->taskId,
            'version' => (int) $result->quotation_version,
            'published_now' => $published,
            'formally_published' => $formallyPublished,
            'files' => count($this->files),
        ]);
    }
    public function failed(Throwable $exception): void
    {
        Log::error('flowtrack.offer_file_transfer_failed', [
            'quote_id' => $this->quoteId,
            'inquiry_id' => $this->inquiryId,
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
        ]);
    }
}
