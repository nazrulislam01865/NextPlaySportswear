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
        $storefrontUser = Auth::guard('web')->user();
        $adminIsAuthenticated = Auth::guard('admin')->user()?->isAdmin() ?? false;

        if ($storefrontUser && ! $adminIsAuthenticated) {
            abort(404);
        }

        return $next($request);
    }
}
