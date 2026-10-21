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

/** A NextPlay Admin publication completes the linked FlowTracker task too. */
final class SyncPublishedQuoteToFlowTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;
    public int $timeout = 90;
    public function __construct(public readonly int $quoteId, public readonly int $version) {}
    public function backoff(): array { return [2, 5, 15, 45, 120, 300]; }
    public function middleware(): array
    {
        return [(new WithoutOverlapping('nextplay-published-quote-'.$this->quoteId))->releaseAfter(3)->expireAfter(115)];
    }

    public function handle(): void
    {
        $quote = BulkQuoteRequest::query()->find($this->quoteId);
        if (! $quote || ! $quote->flowtrack_inquiry_id || (int) $quote->quotation_version !== $this->version
            || ! $quote->quotation_sent_at) return;
        $base = rtrim((string) config('flowtrack.base_url'), '/');
        $token = trim((string) config('flowtrack.token'));
        if ($base === '' || $token === '') throw new RuntimeException('FlowTracker integration is not configured.');
        if (app()->environment('production') && ! str_starts_with($base, 'https://')) {
            throw new RuntimeException('FlowTracker publication callback requires HTTPS.');
        }
        $files = $quote->quoteFiles()->where('category', 'quotation')->where('version', $this->version)->orderBy('id')->get();
        if ($files->isEmpty() || $files->count() > 8) throw new RuntimeException('Published quotation files are missing.');
        $manifest = [];
        foreach ($files as $file) {
            if (! Storage::disk('local')->exists($file->path)) throw new RuntimeException('Published quotation file missing.');
            $path = Storage::disk('local')->path($file->path);
            $manifest[] = [
                'id' => $file->id, 'name' => $file->original_name,
                'size' => (int) $file->size, 'checksum_sha256' => hash_file('sha256', $path),
                'download_path' => '/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/published-files/'.$file->id,
            ];
        }
        $response = Http::acceptJson()->asJson()->withToken($token)
            ->withOptions(['allow_redirects' => false])->connectTimeout(3)->timeout(20)
            ->post($base.'/api/integrations/nextplay/bulk-orders/'.(int) $quote->flowtrack_inquiry_id.'/published-quotation', [
                'quote_id' => $quote->id, 'version' => $this->version,
                'change_request_id' => (int) $quote->last_change_request_id, 'files' => $manifest,
            ]);
        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException('FlowTracker publication callback returned HTTP '.$response->status().': '.substr($response->body(), 0, 250));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('flowtrack.published_quotation_callback_failed', [
            'quote_id' => $this->quoteId, 'version' => $this->version,
            'error' => $exception->getMessage(),
        ]);
    }
}
