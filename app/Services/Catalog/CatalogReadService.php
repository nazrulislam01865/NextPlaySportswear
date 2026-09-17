<?php

namespace App\Services\Catalog;

use App\Services\Storefront\CategoryCatalogService;
use App\Services\Storefront\ProductCatalogCacheService;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application/read-model boundary for the storefront catalog API.
 *
 * Existing catalog services remain the single business/query source shared by
 * Blade and API controllers. This service only orchestrates those services
 * into frontend-neutral read payloads.
 */
final class CatalogReadService
{
    public function __construct(
        private readonly ProductCatalogService $products,
        private readonly CategoryCatalogService $categories,
        private readonly CategoryContentService $categoryContent,
        private readonly ProductCatalogCacheService $cache,
    ) {
    }

    /** @return array<string, mixed> */
    public function products(array $filters): array
    {
        $filters['categories'] = $this->products->normalizeCategoryFilterIds((array) ($filters['categories'] ?? []));
        $filters['sports'] = $this->products->normalizeCategoryFilterIds((array) ($filters['sports'] ?? []));

        return [
            'paginator' => $this->products->searchPaginated($filters),
            'filters' => $filters,
            'active_filter_count' => $this->activeFilterCount($filters, includeTag: true),
            'filter_options' => $this->products->filterOptions($filters),
            'sort_options' => $this->sortOptions(),
            'catalog_version' => $this->cache->versionSuffix(),
        ];
    }

    /** @return array<string, mixed> */
    public function categories(): array
    {
        return [
            'categories' => $this->categories->allProductCategories(),
            'sports' => $this->categories->sports(),
            'filter_tags' => $this->categories->filterTags(),
            'catalog_version' => $this->cache->versionSuffix(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function category(string $slug, array $filters, bool $sortWasProvided): ?array
    {
        $category = $this->categories->findBySlug($slug);
        if (! $category) {
            return null;
        }

        $defaultSort = (string) ($category->default_product_sort ?: 'featured');
        if (! $sortWasProvided) {
            $filters['sort'] = $defaultSort;
        }

        $products = $this->categories->productsFor($category, $filters);
        $activeFilterCount = $this->activeFilterCount($filters, includeTag: false, categoryContext: true);
        $hasFilters = $activeFilterCount > 0 || (string) ($filters['sort'] ?? $defaultSort) !== $defaultSort;

        return [
            'category' => $this->categories->categoryData($category),
            'breadcrumbs' => $this->categories->breadcrumbs($category)
                ->map(fn ($item): array => [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'slug' => (string) $item->slug,
                    'url' => route('categories.show', $item->slug),
                ])->values()->all(),
            'related_categories' => $this->categories->relatedCategories($category),
            'products' => $products,
            'filters' => $filters,
            'active_filter_count' => $activeFilterCount,
            'filter_options' => $this->categories->filterOptions($category),
            'sort_options' => $this->sortOptions(),
            'content_blocks' => $this->categoryContent->resolve($category)->values()->all(),
            'faqs' => $category->faqs
                ->where('is_active', true)
                ->map(fn ($faq): array => [
                    'question' => (string) $faq->question,
                    'answer_html' => (string) $faq->answer_html,
                ])->values()->all(),
            'seo' => [
                'title' => (string) (($category->meta_title ?: $category->name).' | '.config('storefront.name')),
                'description' => (string) ($category->meta_description ?: $category->short_description ?: $category->description ?: ''),
                'canonical' => (string) ($category->canonical_url ?: route('categories.show', $category->slug)),
                'robots' => $hasFilters || ! $category->robots_index
                    ? 'noindex, follow'
                    : ($category->robots_follow ? 'index, follow' : 'index, nofollow'),
                'og_title' => (string) ($category->og_title ?: $category->name),
                'og_description' => (string) ($category->og_description ?: $category->meta_description ?: ''),
                'og_image' => $category->ogImageUrl(),
            ],
            'catalog_version' => $this->cache->versionSuffix(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function product(string $slug): ?array
    {
        $product = $this->products->findFullBySlug($slug);
        if (! $product) {
            return null;
        }

        return [
            'product' => $product,
            'related_products' => $this->products->relatedFor($product),
            'catalog_version' => $this->cache->versionSuffix(),
        ];
    }

    /** @return array<string, mixed> */
    public function suggestions(string $query, int $limit = 8): array
    {
        $query = trim($query);
        $limit = max(1, min(12, $limit));

        if ($query === '') {
            return [
                'query' => '',
                'products' => [],
                'categories' => [],
                'catalog_version' => $this->cache->versionSuffix(),
            ];
        }

        return [
            'query' => $query,
            'products' => $this->products->suggestions($query, $limit),
            'categories' => $this->categories->suggestions($query, min(6, $limit)),
            'catalog_version' => $this->cache->versionSuffix(),
        ];
    }

    /** @return array<int, array{value:string,label:string}> */
    public function sortOptions(): array
    {
        return [
            ['value' => 'featured', 'label' => 'Featured'],
            ['value' => 'best-selling', 'label' => 'Best selling'],
            ['value' => 'newest', 'label' => 'Newest'],
            ['value' => 'price-low', 'label' => 'Price: low to high'],
            ['value' => 'price-high', 'label' => 'Price: high to low'],
            ['value' => 'rating-high', 'label' => 'Highest rated'],
            ['value' => 'name-asc', 'label' => 'Name: A to Z'],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function activeFilterCount(array $filters, bool $includeTag, bool $categoryContext = false): int
    {
        $count = filled($filters['q'] ?? null) ? 1 : 0;
        if ($includeTag) {
            $count += filled($filters['tag'] ?? null) ? 1 : 0;
        }

        $keys = $categoryContext
            ? ['subcategory', 'sports', 'product_types', 'colors', 'materials', 'artwork_methods', 'moq', 'customization', 'availability']
            : ['categories', 'sports', 'product_types', 'colors', 'materials', 'artwork_methods', 'moq', 'customization', 'availability'];

        foreach ($keys as $key) {
            $count += count((array) ($filters[$key] ?? []));
        }

        foreach ((array) ($filters['attributes'] ?? []) as $values) {
            $count += count((array) $values);
        }

        $count += ($filters['min_price'] ?? null) !== null ? 1 : 0;
        $count += ($filters['max_price'] ?? null) !== null ? 1 : 0;
        $count += ($filters['min_rating'] ?? null) !== null ? 1 : 0;

        return $count;
    }

    /** @return array<string, int> */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
