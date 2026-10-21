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
use RuntimeException;

final class TransferFlowTrackBulkQuoteFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 5;
    public int $timeout = 120;
    public function __construct(public readonly int $quoteId, public readonly int $inquiryId, public readonly array $manifest, public readonly string $manifestHash) {}
    public function backoff(): array { return [2, 8, 30, 120, 300]; }
    public function middleware(): array
    {
        return [(new WithoutOverlapping('nextplay-bulk-files-'.$this->quoteId))->releaseAfter(3)->expireAfter(140)];
    }
    public function handle(): void
    {
        $quote = BulkQuoteRequest::query()->find($this->quoteId);
        if (!$quote || (int) ($quote->flowtrack_inquiry_id ?? 0) !== $this->inquiryId
            || ! hash_equals((string) $quote->flowtrack_manifest_hash, $this->manifestHash)) return;
        $base = rtrim((string) config('flowtrack.base_url'), '/');
        $token = trim((string) config('flowtrack.token'));
        if (!$base || !$token) throw new RuntimeException('FlowTracker bulk file transfer is not configured.');
        if (app()->environment('production') && ! str_starts_with($base, 'https://')) {
            throw new RuntimeException('FlowTracker file download must use HTTPS.');
        }
        $keys = [];
        foreach ($this->manifest as $file) {
            $docId = (int) ($file['id'] ?? 0);
            $path = '/api/integrations/nextplay/bulk-orders/'.$this->inquiryId.'/files/'.$docId;
            if ($docId <= 0 || (string) ($file['download_path'] ?? '') !== $path) {
                throw new RuntimeException('Rejected unexpected FlowTracker file download path.');
            }
            $key = 'flowtrack:'.$this->inquiryId.':'.$docId;
            $keys[] = $key;
            if ($quote->quoteFiles()->where('source_key', $key)->exists()) continue;
            if ((int) ($file['size'] ?? 0) > 20971520) throw new RuntimeException('Bulk file too large.');
            $temp = tempnam(sys_get_temp_dir(), 'flowtrack-bulk-');
            if ($temp === false) throw new RuntimeException('Cannot allocate temporary file.');
            try {
                $response = Http::withToken($token)->withOptions(['allow_redirects' => false])
                    ->connectTimeout(3)->timeout(90)->sink($temp)->get($base.$path);
                if (!$response->successful()) throw new RuntimeException('FlowTracker download HTTP '.$response->status());
                if (filesize($temp) > 20971520) throw new RuntimeException('FlowTracker file exceeds limit.');
                if (! hash_equals(strtolower((string) $file['checksum_sha256']), hash_file('sha256', $temp))) {
                    throw new RuntimeException('Bulk file checksum verification failed.');
                }
                $originalName = substr(basename(str_replace('\\', '/', (string) $file['name'])), 0, 190);
                if ($originalName === '') throw new RuntimeException('Missing bulk file name.');
                $target = 'bulk-quote-private/'.$quote->id.'/flowtrack/'.bin2hex(random_bytes(16)).'-'.basename($originalName);
                $stream = fopen($temp, 'rb');
                try { $written = Storage::disk('local')->writeStream($target, $stream); }
                finally { if (is_resource($stream)) fclose($stream); }
                if (! $written) throw new RuntimeException('Cannot store imported bulk file.');
                $quote->quoteFiles()->firstOrCreate(['source_key' => $key], [
                    'bulk_quote_message_id' => null, 'uploaded_by' => null,
                    'version' => 1, 'category' => 'client_attachment', 'path' => $target,
                    'original_name' => $originalName, 'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
                    'size' => (int) filesize($temp),
                ]);
            } finally { @unlink($temp); }
        }
        // Reconcile deletions only after all current downloads have succeeded.
        $stale = $quote->quoteFiles()->where('category', 'client_attachment')
            ->where('source_key', 'like', 'flowtrack:'.$this->inquiryId.':%')
            ->when($keys !== [], fn ($query) => $query->whereNotIn('source_key', $keys))->get();
        foreach ($stale as $file) {
            $file->delete();
            Storage::disk('local')->delete($file->path);
        }
    }
}
