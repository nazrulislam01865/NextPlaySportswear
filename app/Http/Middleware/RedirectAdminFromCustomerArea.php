<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminFromCustomerArea
{
    /**
     * Keep the storefront and admin guards independent.
     *
     * An active admin session must never turn a storefront account, wishlist,
     * or checkout link into an automatic admin-dashboard redirect. Customer
     * authentication continues to be enforced by the web guard on the routes
     * that require it, while the admin area remains available only by its
     * direct /admin URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
