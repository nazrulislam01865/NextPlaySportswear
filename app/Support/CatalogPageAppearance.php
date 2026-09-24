<?php

namespace App\Support;

use App\Models\CatalogPageSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CatalogPageAppearance
{
    private const CACHE_KEY = 'storefront.catalog-page-appearance.v1';

    /** @return array{image:?string,color:?string} */
    public static function productsBanner(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 600, function (): array {
                if (! Schema::hasTable('catalog_page_settings')) {
                    return ['image' => null, 'color' => null];
                }

                $settings = CatalogPageSetting::query()->first();

                return [
                    'image' => $settings?->productsBannerUrl(),
                    'color' => filled($settings?->products_banner_color)
                        ? (string) $settings->products_banner_color
                        : null,
                ];
            });
        } catch (Throwable) {
            return ['image' => null, 'color' => null];
        }
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
