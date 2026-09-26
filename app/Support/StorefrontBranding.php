<?php

namespace App\Support;

use App\Models\StorefrontBrandingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class StorefrontBranding
{
    private const CACHE_KEY = 'storefront.branding.logo.v1';

    /** @var array{logo_path:?string}|null */
    private static ?array $memoized = null;

    public static function logoUrl(): string
    {
        $settings = self::settings();
        $storedPath = $settings['logo_path'];

        if (filled($storedPath)) {
            $url = PublicMedia::url($storedPath);
            if (filled($url)) {
                return (string) $url;
            }
        }

        return self::configuredFallbackLogoUrl();
    }

    public static function hasCustomLogo(): bool
    {
        return filled(self::settings()['logo_path']);
    }

    public static function logoPath(): ?string
    {
        return self::settings()['logo_path'];
    }

    public static function flushCache(): void
    {
        self::$memoized = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{logo_path:?string} */
    private static function settings(): array
    {
        if (self::$memoized !== null) {
            return self::$memoized;
        }

        try {
            return self::$memoized = Cache::rememberForever(self::CACHE_KEY, static function (): array {
                if (! Schema::hasTable('storefront_branding_settings')) {
                    return ['logo_path' => null];
                }

                $setting = StorefrontBrandingSetting::query()->first();

                return ['logo_path' => filled($setting?->logo_path) ? (string) $setting->logo_path : null];
            });
        } catch (Throwable) {
            return self::$memoized = ['logo_path' => null];
        }
    }

    private static function configuredFallbackLogoUrl(): string
    {
        $configuredLogo = trim((string) config('storefront.logo', '/images/logo.png'));
        if ($configuredLogo === '') {
            $configuredLogo = '/images/logo.png';
        }

        if (preg_match('/^https?:\/\//i', $configuredLogo)) {
            return $configuredLogo;
        }

        return asset(ltrim($configuredLogo, '/'));
    }
}
