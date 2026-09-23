<?php

namespace App\Support;

use App\Models\HomepageSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class StorefrontDisplaySettings
{
    private const CACHE_KEY = 'storefront.display-settings.v1';

    /** @var array<string, mixed>|null */
    private static ?array $runtimeSettings = null;

    public static function showDefaultProductRatings(): bool
    {
        $fallback = (bool) config('storefront.product_cards.show_default_rating', true);

        return self::booleanValue(
            self::settings()['show_default_product_ratings'] ?? null,
            $fallback,
        );
    }

    public static function flushCache(): void
    {
        self::$runtimeSettings = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private static function settings(): array
    {
        if (self::$runtimeSettings !== null) {
            return self::$runtimeSettings;
        }

        $ttl = max(1, (int) config('storefront.homepage_sections_cache_seconds', 600));

        try {
            $settings = Cache::remember(self::CACHE_KEY, $ttl, function (): array {
                if (! Schema::hasTable('homepage_sections') || ! Schema::hasColumn('homepage_sections', 'settings')) {
                    return [];
                }

                $section = HomepageSection::query()
                    ->select(['id', 'settings'])
                    ->where('key', 'testimonials')
                    ->first();

                return is_array($section?->settings) ? $section->settings : [];
            });
        } catch (Throwable) {
            $settings = [];
        }

        return self::$runtimeSettings = is_array($settings) ? $settings : [];
    }

    private static function booleanValue(mixed $value, bool $fallback): bool
    {
        if ($value === null) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $fallback;
    }
}
