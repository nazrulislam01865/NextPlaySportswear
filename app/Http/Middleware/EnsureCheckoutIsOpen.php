<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCheckoutIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('storefront.checkout_enabled', false)) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            return redirect()->route('cart.index')->with(
                'status',
                'Online checkout is temporarily unavailable while we prepare our store. You can still browse products and save items to your cart.'
            );
        }

        return response('Online checkout is temporarily unavailable.', 503);
    }
}
