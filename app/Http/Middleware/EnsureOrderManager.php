<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderManager
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');

        abort_unless(Auth::guard('admin')->user()?->canManageOrders(), 403, 'Order management access is required.');

        return $next($request);
    }
}
