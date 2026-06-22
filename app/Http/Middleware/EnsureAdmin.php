<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');

        if (! Auth::guard('admin')->user()?->isAdmin()) {
            Auth::guard('admin')->logout();
            abort(403, 'Administrator access is required.');
        }

        return $next($request);
    }
}
