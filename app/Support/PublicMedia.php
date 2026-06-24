<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PublicMedia
{
    public static function url(?string $path, ?string $externalUrl = null, ?string $fallback = null): ?string
    {
        if (filled($path)) {
            return self::storedPathUrl((string) $path);
        }

        $external = self::normalizeExternalUrl($externalUrl);

        return $external ?? $fallback;
    }

    public static function storedPathUrl(string $path): string
    {
        $normalized = self::normalizePath($path);

        return '/media/'.collect(explode('/', $normalized))
            ->map(static fn (string $segment): string => rawurlencode($segment))
            ->implode('/');
    }

    public static function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path) ?: '';

        return ltrim($path, '/');
    }

    public static function storedPathFromUrl(?string $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        foreach (['/storage/', '/media/'] as $prefix) {
            if (str_starts_with($url, $prefix)) {
                return self::normalizePath(Str::after($url, $prefix));
            }
        }

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $prefix = collect(['/storage/', '/media/'])->first(
            static fn (string $candidate): bool => str_starts_with($path, $candidate)
        );
        if (! $prefix) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $requestHost = app()->runningInConsole() ? '' : strtolower((string) request()->getHost());
        $localHosts = ['localhost', '127.0.0.1', '::1'];

        if (in_array($host, $localHosts, true) || $host === $appHost || $host === $requestHost) {
            return self::normalizePath(Str::after($path, $prefix));
        }

        return null;
    }

    private static function normalizeExternalUrl(?string $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        $storedPath = self::storedPathFromUrl($url);

        return $storedPath !== null ? self::storedPathUrl($storedPath) : $url;
    }

}
