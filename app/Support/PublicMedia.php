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
        $baseUrl = rtrim((string) config('filesystems.disks.public.url', '/media'), '/');

        // Cloud deployments sometimes have public/storage as a copied directory
        // instead of a real symlink. Use the application-owned /media route for
        // same-application /storage URLs so newly uploaded images always resolve.
        if ($baseUrl === '' || self::isApplicationStorageBaseUrl($baseUrl)) {
            $baseUrl = '/media';
        }

        $encodedPath = collect(explode('/', $normalized))
            ->map(static fn (string $segment): string => rawurlencode($segment))
            ->implode('/');

        return $baseUrl.'/'.$encodedPath;
    }

    public static function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        if (preg_match('#^https?://#i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $path = preg_replace('#/+#', '/', $path) ?: '';
        $path = ltrim($path, '/');

        foreach (['storage/app/public/', 'public/storage/', 'storage/', 'media/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = Str::after($path, $prefix);
                break;
            }
        }

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

        $configuredBaseUrl = rtrim((string) config('filesystems.disks.public.url', '/media'), '/');
        if ($configuredBaseUrl !== '' && ! preg_match('#^https?://#i', $configuredBaseUrl)) {
            $configuredPrefix = '/'.trim($configuredBaseUrl, '/').'/';
            if ($configuredPrefix !== '//' && str_starts_with($url, $configuredPrefix)) {
                return self::normalizePath(Str::after($url, $configuredPrefix));
            }
        }

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $requestHost = app()->runningInConsole() ? '' : strtolower((string) request()->getHost());
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        $isApplicationHost = in_array($host, $localHosts, true) || $host === $appHost || $host === $requestHost;

        if (! $isApplicationHost) {
            return null;
        }

        $prefix = collect(['/storage/', '/media/'])->first(
            static fn (string $candidate): bool => str_starts_with($path, $candidate)
        );

        if ($prefix) {
            return self::normalizePath(Str::after($path, $prefix));
        }

        if (preg_match('#^https?://#i', $configuredBaseUrl)) {
            $configuredHost = strtolower((string) parse_url($configuredBaseUrl, PHP_URL_HOST));
            $configuredPath = '/'.trim((string) parse_url($configuredBaseUrl, PHP_URL_PATH), '/');

            if (
                $configuredHost === $host
                && $configuredPath !== '/'
                && ($path === $configuredPath || str_starts_with($path, $configuredPath.'/'))
            ) {
                return self::normalizePath(ltrim(substr($path, strlen($configuredPath)), '/'));
            }
        }

        return null;
    }

    private static function isApplicationStorageBaseUrl(string $baseUrl): bool
    {
        if ($baseUrl === '/storage') {
            return true;
        }

        if (! preg_match('#^https?://#i', $baseUrl)) {
            return false;
        }

        $path = '/'.trim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
        if ($path !== '/storage') {
            return false;
        }

        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $requestHost = app()->runningInConsole() ? '' : strtolower((string) request()->getHost());

        return $host === ''
            || in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || ($appHost !== '' && $host === $appHost)
            || ($requestHost !== '' && $host === $requestHost);
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
