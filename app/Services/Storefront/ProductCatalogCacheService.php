<?php

namespace App\Services\Storefront;

use Illuminate\Support\Facades\Cache;

class ProductCatalogCacheService
{
    public const PRODUCT_VERSION_KEY = 'catalog.products.version';
    public const CATEGORY_VERSION_KEY = 'catalog.category-facets.version';

    public function versionSuffix(): string
    {
        return $this->versionSuffixFor(
            (int) Cache::get(self::CATEGORY_VERSION_KEY, 1),
            (int) Cache::get(self::PRODUCT_VERSION_KEY, 1),
        );
    }

    public function flush(): void
    {
        $categoryVersion = (int) Cache::get(self::CATEGORY_VERSION_KEY, 1);
        $productVersion = (int) Cache::get(self::PRODUCT_VERSION_KEY, 1);

        $this->forgetKnownKeys($this->versionSuffixFor($categoryVersion, $productVersion));
        $this->forgetKnownKeys((string) $categoryVersion); // legacy keys used before the product cache version existed

        Cache::forever(self::PRODUCT_VERSION_KEY, $productVersion + 1);
    }

    private function versionSuffixFor(int $categoryVersion, int $productVersion): string
    {
        return $categoryVersion.'.'.$productVersion;
    }

    private function forgetKnownKeys(string $versionSuffix): void
    {
        Cache::forget('catalog.product-summaries.'.$versionSuffix);

        foreach ([4, 6, 8, 10, 12, 16, 24] as $limit) {
            Cache::forget('catalog.homepage-featured.'.$versionSuffix.'.'.$limit);
            Cache::forget('catalog.homepage-latest.'.$versionSuffix.'.'.$limit);
            Cache::forget('catalog.homepage-best-selling.'.$versionSuffix.'.'.$limit);
        }
    }
}
