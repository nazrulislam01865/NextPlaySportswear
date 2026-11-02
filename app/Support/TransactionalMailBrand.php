<?php

namespace App\Support;

/**
 * Presentation-only helpers for email clients. No database migration and no
 * change to the normal storefront header logo are required.
 */
final class TransactionalMailBrand
{
    public static function headerLogoUrl(): string
    {
        $logoUrl = trim(StorefrontBranding::logoUrl());

        if (preg_match('#^https?://#i', $logoUrl)) {
            return $logoUrl;
        }

        // /media/... links work in browsers, but an email needs an absolute URL.
        // Use configured APP_URL, not a request-supplied Host header.
        return rtrim((string) config('app.url'), '/').'/'.ltrim($logoUrl, '/');
    }

    public static function lockIconUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/images/email/password-changed-lock.png';
    }
}
