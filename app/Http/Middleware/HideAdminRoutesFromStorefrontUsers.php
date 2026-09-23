<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HideAdminRoutesFromStorefrontUsers
{
    /**
     * Keep the administration surface undiscoverable from an active
     * storefront account. An administrator must use a separate admin
     * session (or sign out of the customer account first).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Once an administrator is authenticated, do not resolve the storefront
        // guard on admin requests. Resolving a remembered customer here can
        // unnecessarily couple the two guards even though the admin session is
        // already valid.
        if (Auth::guard('admin')->user()?->isAdmin()) {
            return $next($request);
        }

        if (Auth::guard('web')->user()) {
            abort(404);
        }

        return $next($request);
    }
}
