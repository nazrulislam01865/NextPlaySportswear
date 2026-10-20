<?php

namespace App\Jobs\Integrations;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class NotifyFlowTrackCatalogChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [1, 5, 20, 60];

    public function __construct(public readonly string $type, public readonly int $id) {}

    public function handle(): void
    {
        if (! config('flowtrack.catalog_events_enabled', false)) return;
        $base = (string) config('flowtrack.base_url');
        $token = (string) config('flowtrack.token');
        if ($base === '' || $token === '') throw new RuntimeException('Missing FlowTracker callback configuration.');
        $result = Http::withToken($token)->acceptJson()->connectTimeout(2)->timeout(8)->withoutRedirecting()
            ->post($base.'/api/integrations/nextplay/catalog/changed', ['type' => $this->type, 'id' => $this->id]);
        if (! $result->successful()) throw new RuntimeException('FlowTracker catalog callback HTTP '.$result->status());
    }
}
