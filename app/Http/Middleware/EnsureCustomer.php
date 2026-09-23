<?php

namespace App\Http\Middleware;

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
            $request->session()->forget(EnforceCustomerSessionVersion::SESSION_KEY);

            // Do not invalidate the whole browser session: Laravel stores all
            // session guards in the same payload, so that would also log an
            // administrator out. Rotate safely after clearing only web auth.
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in with an active customer account.',
            ]);
        }

        return $next($request);
    }
}
