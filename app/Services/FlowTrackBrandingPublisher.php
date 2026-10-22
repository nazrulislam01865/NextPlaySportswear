<?php

namespace App\Services;

use App\Support\FlowTrackBrandingSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** An update-time push, not polling from either site's visitor requests. */
final class FlowTrackBrandingPublisher
{
    public function publish(): bool
    {
        $base = rtrim((string) config('flowtrack.base_url'), '/');
        $token = trim((string) config('flowtrack.token'));
        if (!config('flowtrack.enabled') || !$base || !$token) return false;

        try {
            $result = Http::withToken($token)->acceptJson()->connectTimeout(2)->timeout(5)
                ->post($base.'/api/integrations/nextplay/branding', ['branding' => FlowTrackBrandingSnapshot::current()]);
            if ($result->successful()) return true;
            Log::warning('nps.branding.sync.failed', ['status' => $result->status()]);
        } catch (\Throwable $exception) {
            Log::warning('nps.branding.sync.unreachable', ['message' => $exception->getMessage()]);
        }
        return false;
    }
}
