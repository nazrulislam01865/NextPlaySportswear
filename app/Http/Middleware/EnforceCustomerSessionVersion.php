<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforceCustomerSessionVersion
{
    public const SESSION_KEY = 'customer_auth_session_version';

    public function handle(Request $request, Closure $next): Response
    {
        // Customer-session enforcement must never mutate an administrator
        // session. Both guards share Laravel's browser session store, so an
        // invalid/stale storefront session could otherwise invalidate the
        // active admin session while navigating between /admin pages.
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        $guard = Auth::guard('web');
        $customer = $guard->user();

        if ($customer?->role === 'customer' && ! $customer->is_active) {
            $guard->logout();
            $request->session()->forget(self::SESSION_KEY);
            // Preserve unrelated guards (especially an administrator session)
            // while rotating the shared session identifier and CSRF token.
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This customer account is suspended and cannot be used to sign in.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'This customer account is currently suspended. Please contact support if you believe this is a mistake.',
                ]);
        }

        if ($customer?->isCustomer()) {
            $storedVersion = $request->session()->get(self::SESSION_KEY);
            $currentVersion = (int) $customer->auth_session_version;

            /*
             * A session restored from a still-valid remember-me cookie starts
             * without our session version key. In that specific case bind the
             * fresh session to the current version. Otherwise a missing version
             * represents a pre-deployment customer session and is treated as
             * stale once so it cannot silently survive a password reset.
             */
            if ($storedVersion === null && method_exists($guard, 'viaRemember') && $guard->viaRemember()) {
                $request->session()->put(self::SESSION_KEY, $currentVersion);
                $storedVersion = $currentVersion;
            }

            if ($storedVersion === null || (int) $storedVersion !== $currentVersion) {
                /*
                 * logoutCurrentDevice() removes only the current web-guard
                 * session and does not rotate remember_token again. That avoids
                 * an old stale browser invalidating a newer remember-me login.
                 */
                if (method_exists($guard, 'logoutCurrentDevice')) {
                    $guard->logoutCurrentDevice();
                } else {
                    // Compatibility fallback for alternative session guards.
                    $guard->logout();
                }

                $request->session()->forget(self::SESSION_KEY);
                $request->session()->regenerate(true);
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your customer session is no longer valid. Please sign in again.',
                    ], 401);
                }

                return redirect()
                    ->route('login')
                    ->with('status', 'For your security, please sign in again because your account credentials changed.');
            }
        }

        $response = $next($request);

        /*
         * Login and registration begin as guest requests. Store the current
         * version after the controller authenticates the customer so every new
         * customer session is bound to the account security version.
         */
        $customer = $guard->user();

        if ($customer?->isCustomer()) {
            $request->session()->put(
                self::SESSION_KEY,
                (int) $customer->auth_session_version,
            );
        } else {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $response;
    }
}
