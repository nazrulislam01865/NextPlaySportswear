<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates FlowTrack's server-to-server callbacks into NextPlay.
 */
final class VerifyFlowTrackIntegration
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('flowtrack.enabled')) {
            return new JsonResponse(['message' => 'FlowTrack integration is disabled.'], 503);
        }

        $configuredToken = trim((string) config('flowtrack.token'));
        if ($configuredToken === '') {
            return new JsonResponse(['message' => 'FlowTrack integration token is not configured.'], 503);
        }

        $providedToken = trim((string) ($request->bearerToken() ?? ''));
        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            Log::warning('nextplay.flowtrack.unauthorized_callback', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return new JsonResponse(['message' => 'Unauthorized FlowTrack integration request.'], 401);
        }

        return $next($request);
    }
}
