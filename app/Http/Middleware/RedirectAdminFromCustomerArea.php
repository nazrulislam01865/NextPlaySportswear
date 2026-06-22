<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminFromCustomerArea
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Administrator accounts are restricted to the admin panel.');
        }

        return $next($request);
    }
}
