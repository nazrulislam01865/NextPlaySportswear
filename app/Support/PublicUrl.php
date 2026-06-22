<?php

namespace App\Support;

final class PublicUrl
{
    public static function isAllowed(mixed $value, bool $allowContactSchemes = false): bool
    {
        $url = trim((string) $value);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        if ($url === '#') {
            return true;
        }

        if (str_starts_with($url, '#')) {
            return preg_match('/^#[A-Za-z][A-Za-z0-9_:.-]*$/', $url) === 1;
        }

        // A single leading slash is an application-relative URL. Two leading
        // slashes are protocol-relative and are deliberately rejected.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return ! str_contains($url, '\\') && ! str_contains($url, ' ');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false
                && filled(parse_url($url, PHP_URL_HOST));
        }

        if ($allowContactSchemes && $scheme === 'mailto') {
            return filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false;
        }

        if ($allowContactSchemes && $scheme === 'tel') {
            return preg_match('/^tel:\+?[0-9().\-\s]{5,30}$/', $url) === 1;
        }

        return false;
    }
}
