<?php

namespace App\Http\Middleware;

use App\Support\Api\RequestId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class ApiRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/v1/*')) {
            return $next($request);
        }

        $requestId = RequestId::ensure($request);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set(RequestId::HEADER, $requestId);

        return $response;
    }
}
