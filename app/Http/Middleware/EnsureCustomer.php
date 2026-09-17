<?php

namespace App\Http\Middleware;

use App\Support\Api\RequestId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('web');

        $user = Auth::guard('web')->user();
        if (! $user?->isCustomer()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                $requestId = RequestId::ensure($request);

                return response()->json([
                    'message' => 'Forbidden.',
                    'request_id' => $requestId,
                ], 403)->header(RequestId::HEADER, $requestId);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in with an active customer account.',
            ]);
        }

        return $next($request);
    }
}
