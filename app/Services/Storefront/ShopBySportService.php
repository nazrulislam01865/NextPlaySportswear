<?php

namespace App\Services\Storefront;

use Throwable;

class ShopBySportService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $runtimeCategories = null;

    public function __construct(
        private readonly CategoryCatalogService $catalog,
        private readonly HomepageSectionService $homepageSections,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        if ($this->runtimeCategories !== null) {
            return $this->runtimeCategories;
        }

        $categoryIds = $this->configuredCategoryIds();

        if ($categoryIds !== []) {
            $categories = $this->catalog->categoriesByIds($categoryIds, count($categoryIds));

            if ($categories !== []) {
                return $this->runtimeCategories = $categories;
            }
        }

        return $this->runtimeCategories = $this->catalog->sports();
    }

    /** @return array<int, int> */
    private function configuredCategoryIds(): array
    {
        try {
            $section = collect($this->homepageSections->sections())
                ->firstWhere('key', 'shop_by_sport');
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        if (! is_array($section) || ! is_array($section['items'] ?? null)) {
            return [];
        }

        return collect($section['items'])
            ->pluck('category_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
