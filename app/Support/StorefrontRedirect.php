<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class StorefrontRedirect
{
    private const SESSION_KEY = 'storefront.auth.intended_url';

    /**
     * Capture a safe storefront destination before rendering an auth page.
     */
    public static function capture(Request $request): ?string
    {
        $candidates = [
            $request->query('redirect'),
            $request->session()->get('url.intended'),
            $request->session()->get(self::SESSION_KEY),
            $request->headers->get('referer'),
        ];

        foreach ($candidates as $candidate) {
            $safe = self::sanitize($request, $candidate);

            if ($safe !== null) {
                $request->session()->put(self::SESSION_KEY, $safe);

                return $safe;
            }
        }

        return null;
    }

    /**
     * Resolve and consume the post-authentication destination.
     */
    public static function intended(Request $request, string $fallback): string
    {
        $candidates = [
            $request->input('redirect'),
            $request->session()->get(self::SESSION_KEY),
            $request->session()->get('url.intended'),
        ];

        $destination = null;

        foreach ($candidates as $candidate) {
            $destination = self::sanitize($request, $candidate);

            if ($destination !== null) {
                break;
            }
        }

        // Laravel's shared intended URL can contain an old admin destination.
        // Always clear both keys after a customer authentication attempt.
        $request->session()->forget([self::SESSION_KEY, 'url.intended']);

        return $destination ?? $fallback;
    }

    /**
     * Only allow same-origin storefront GET destinations.
     */
    public static function sanitize(Request $request, mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);

        if ($candidate === '' || Str::startsWith($candidate, ['//', '\\\\'])) {
            return null;
        }

        $parts = parse_url($candidate);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['host'])) {
            if (! hash_equals(Str::lower($request->getHost()), Str::lower((string) $parts['host']))) {
                return null;
            }

            $requestPort = $request->getPort();
            $candidatePort = isset($parts['port']) ? (int) $parts['port'] : null;

            if ($candidatePort !== null && $candidatePort !== $requestPort) {
                return null;
            }
        } elseif (! Str::startsWith($candidate, '/')) {
            return null;
        }

        $path = '/'.ltrim((string) ($parts['path'] ?? '/'), '/');
        $normalizedPath = Str::lower(rtrim($path, '/') ?: '/');

        foreach (['/admin', '/login', '/register', '/logout', '/forgot-password'] as $blockedPath) {
            if ($normalizedPath === $blockedPath || Str::startsWith($normalizedPath, $blockedPath.'/')) {
                return null;
            }
        }

        if (isset($parts['host'])) {
            return $candidate;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }
}
