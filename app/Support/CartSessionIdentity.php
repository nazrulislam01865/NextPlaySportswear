<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CartSessionIdentity
{
    public const SESSION_KEY = 'nextplay_cart.identity';

    public static function current(?Request $request = null): string
    {
        $session = self::sessionStore($request);

        if (! $session instanceof Session) {
            return '';
        }

        $identity = trim((string) $session->get(self::SESSION_KEY, ''));
        if ($identity !== '') {
            return $identity;
        }

        // Seed from Laravel's current session id so existing guest carts that
        // were persisted before this compatibility layer remain reachable.
        // After that, keep the identity as a session value so cart ownership
        // remains stable even when the underlying session id is regenerated.
        $identity = trim((string) $session->getId());
        if ($identity === '') {
            $identity = (string) Str::uuid();
        }

        $session->put(self::SESSION_KEY, $identity);

        return $identity;
    }

    private static function sessionStore(?Request $request): ?Session
    {
        if ($request instanceof Request) {
            return $request->hasSession() ? $request->session() : null;
        }

        if (app()->bound('request')) {
            $currentRequest = app('request');

            if ($currentRequest instanceof Request && $currentRequest->hasSession()) {
                return $currentRequest->session();
            }
        }

        if (app()->bound('session.store')) {
            $store = app('session.store');

            return $store instanceof Session ? $store : null;
        }

        return null;
    }
}
