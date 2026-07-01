<?php

namespace App\Services\Storefront;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryCatalogService
{
    /** @var array<int, array<int>> */
    private array $runtimeListingCategoryIds = [];

    /** @var Collection<int, array{id:int,parent_id:int|null}>|null */
    private ?Collection $runtimeCategoryParentRows = null;

    public function __construct(
        private readonly ProductCatalogService $productCatalogService,
        private readonly CategoryTreeService $treeService,
    ) {
    }

    public function findBySlug(string $slug): ?Category
    {
        $category = Category::query()
            ->storefrontReachable()
            ->with([
                'parent',
                'children' => fn ($query) => $query->storefrontReachable(),
                'filters.values',
                'contentBlocks',
                'faqs',
            ])
            ->where('slug', $slug)
            ->first();

        if ($category) {
            $this->attachProductCounts(collect([$category]));
            $this->attachProductCounts($category->children);
        }

        return $category;
    }

    public function collections(): array
    {
        $categories = $this->topLevelQuery()
            ->where('category_type', '!=', 'sport')
            ->get();

        $this->attachProductCounts($categories);

        return $categories->map(fn (Category $category) => $this->categoryData($category))->all();
    }

    public function sports(): array
    {
        $categories = $this->topLevelQuery()
            ->where('category_type', 'sport')
            ->get();

        $this->attachProductCounts($categories);

        return $categories->map(fn (Category $category) => $this->categoryData($category))->all();
    }

    public function filterTags(): array
    {
        return [
            ['slug' => 'sport', 'name' => 'Sports'],
            ['slug' => 'collection', 'name' => 'Collections'],
            ['slug' => 'apparel', 'name' => 'Apparel'],
            ['slug' => 'accessory', 'name' => 'Accessories'],
            ['slug' => 'promotional', 'name' => 'Promotional'],
        ];
    }

    public function categoryData(Category $category): array
    {
        $count = $category->products_count ?? $this->productCount($category);

        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'group' => $category->category_type,
            'tags' => array_values(array_unique(array_filter([$category->category_type, $category->display_type]))),
            'eyebrow' => $category->eyebrow ?: 'Custom sportswear',
            'title' => $category->name,
            'short_title' => $category->short_title ?: $category->name,
            'description' => $category->short_description ?: $category->description,
            'description_html' => $category->description_html,
            'best_for' => $category->best_for,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description ?: $category->short_description ?: $category->description,
            'image' => $category->thumbnailUrl(),
            'banner' => $category->bannerUrl(),
            'mobile_banner' => $category->bannerUrl(true),
            'alt' => $category->thumbnail_alt ?: $category->image_alt ?: $category->name,
            'banner_alt' => $category->banner_alt ?: $category->name,
            'highlights' => $category->highlights ?? [],
            'link_label' => $category->cta_label ?: 'View Category',
            'product_count' => $count,
            'is_featured' => (bool) $category->is_featured,
            'url' => route('categories.show', $category->slug),
        ];
    }

    public function productsFor(Category $category, array $filters): LengthAwarePaginator
    {
        $categoryIds = $this->categoryIdsForListing($category);

        $query = $this->applyCategoryProductFilter(Product::query()->published(), $categoryIds)
            ->select('products.*')
            ->selectSub($this->categoryFeaturedSelect($categoryIds), 'category_featured')
            ->selectSub($this->categorySortSelect($categoryIds), 'category_sort')
            ->with($this->productCatalogService->listingRelations());

        if ($filters['q'] !== '') {
            $needle = $filters['q'];
            $query->where(function (Builder $builder) use ($needle): void {
                $builder->where('products.name', 'like', "%{$needle}%")
                    ->orWhere('products.sku', 'like', "%{$needle}%")
                    ->orWhere('products.brand', 'like', "%{$needle}%")
                    ->orWhere('products.product_type', 'like', "%{$needle}%");
            });
        }

        if ($filters['subcategory'] !== []) {
            // A crafted query string must not be able to use categories outside
            // the current category subtree to change the listing semantics.
            $selected = Category::query()
                ->storefrontReachable()
                ->whereIn('id', array_intersect($filters['subcategory'], $categoryIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($selected !== []) {
                $this->applyCategoryProductFilter($query, $selected);
            }
        }

        $allowedAttributeSlugs = $this->filterableAttributeSlugsForListing($category, $categoryIds);

        foreach ($filters['attributes'] as $attributeSlug => $valueSlugs) {
            if (! in_array($attributeSlug, $allowedAttributeSlugs, true)) {
                continue;
            }

            $query->whereHas('attributeValues', function (Builder $builder) use ($attributeSlug, $valueSlugs): void {
                $builder->whereIn('attribute_values.slug', $valueSlugs)
                    ->whereHas('attribute', fn (Builder $attributeQuery) => $attributeQuery
                        ->where('slug', $attributeSlug)
                        ->where('is_active', true)
                        ->where('is_filterable', true));
            });
        }

        if ($filters['min_price'] !== null) {
            $query->where('products.base_price', '>=', $filters['min_price']);
        }
        if ($filters['max_price'] !== null) {
            $query->where('products.base_price', '<=', $filters['max_price']);
        }
        if ($filters['in_stock']) {
            $query->where(fn (Builder $builder) => $builder
                ->where('products.track_inventory', false)
                ->orWhere('products.stock_quantity', '>', 0)
                ->orWhere('products.allow_backorder', true));
        }
        if ($filters['customizable']) {
            $query->where('products.is_customizable', true);
        }

        match ($filters['sort']) {
            'price-low' => $query->orderBy('products.base_price'),
            'price-high' => $query->orderByDesc('products.base_price'),
            'name-asc' => $query->orderBy('products.name'),
            'newest' => $query->orderByDesc('products.published_at')->orderByDesc('products.id'),
            default => $query
                ->orderByDesc('category_featured')
                ->orderByRaw('COALESCE(category_sort, products.sort_order, 999999) ASC')
                ->orderByDesc('products.is_featured')
                ->orderBy('products.sort_order')
                ->orderBy('products.name'),
        };

        $paginator = $query->paginate(config('catalog.category_page_size', 24))->withQueryString();
        $paginator->through(fn (Product $product): array => $this->productCatalogService->fromListingModel($product));

        return $paginator;
    }

    public function filterOptions(Category $category): array
    {
        $categoryIds = $this->categoryIdsForListing($category);
        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.category-facets.'.$category->id.'.'.$cacheVersion;

        return Cache::remember($cacheKey, config('catalog.facets_cache_seconds', 300), function () use ($category, $categoryIds): array {
            $this->attachProductCounts($category->children);
            $childCategories = $category->children->map(fn (Category $child) => [
                'id' => $child->id,
                'label' => $child->name,
                'slug' => $child->slug,
                'count' => (int) ($child->products_count ?? 0),
            ])->filter(fn (array $child) => $child['count'] > 0)->values()->all();

            $listing = $this->productListingSubquery($categoryIds);

            $valueCounts = DB::table('attribute_value_product as avp')
                ->joinSub($listing, 'category_listing', fn ($join) => $join->on('category_listing.product_id', '=', 'avp.product_id'))
                ->join('attribute_values as av', 'av.id', '=', 'avp.attribute_value_id')
                ->join('products as p', 'p.id', '=', 'avp.product_id')
                ->where('p.status', 'active')
                ->where('p.is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('p.published_at')->orWhere('p.published_at', '<=', now());
                })
                ->where('av.is_active', true)
                ->selectRaw('avp.attribute_value_id, COUNT(DISTINCT p.id) AS aggregate')
                ->groupBy('avp.attribute_value_id')
                ->pluck('aggregate', 'avp.attribute_value_id');

            $configuredAttributes = $category->filters
                ->filter(fn (CatalogAttribute $attribute) => $attribute->is_active && $attribute->is_filterable)
                ->values();

            $configuredAttributeIds = $configuredAttributes
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $attributeValueIds = $valueCounts->keys()
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->all();

            $attributeIdsWithProducts = $attributeValueIds === []
                ? collect()
                : DB::table('attribute_values as av')
                    ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                    ->whereIn('av.id', $attributeValueIds)
                    ->where('a.is_active', true)
                    ->where('a.is_filterable', true)
                    ->whereNull('a.deleted_at')
                    ->orderBy('a.sort_order')
                    ->orderBy('a.name')
                    ->pluck('av.attribute_id')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();

            $autoAttributes = CatalogAttribute::query()
                ->whereIn('id', $attributeIdsWithProducts->diff($configuredAttributeIds)->all())
                ->where('is_active', true)
                ->where('is_filterable', true)
                ->with('values')
                ->ordered()
                ->get();

            $attributes = $configuredAttributes
                ->concat($autoAttributes)
                ->unique('id')
                ->map(function (CatalogAttribute $attribute) use ($valueCounts): array {
                    $values = $attribute->values
                        ->where('is_active', true)
                        ->map(function ($value) use ($valueCounts): array {
                            return [
                                'id' => $value->id,
                                'label' => $value->label,
                                'slug' => $value->slug,
                                'color_hex' => $value->color_hex,
                                'image' => $value->publicImageUrl(),
                                'count' => (int) ($valueCounts[$value->id] ?? 0),
                            ];
                        })
                        ->filter(fn (array $value) => $value['count'] > 0)
                        ->values()
                        ->all();

                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->pivot?->label ?: $attribute->name,
                        'slug' => $attribute->slug,
                        'display_type' => $attribute->display_type,
                        'is_expanded' => (bool) ($attribute->pivot?->is_expanded ?? true),
                        'values' => $values,
                    ];
                })
                ->filter(fn (array $attribute) => $attribute['values'] !== [])
                ->values()
                ->all();

            $priceCeiling = (float) $this->applyCategoryProductFilter(Product::query()->published(), $categoryIds)
                ->max('base_price');

            return [
                'subcategories' => $childCategories,
                'attributes' => $attributes,
                'price_ceiling' => max(100, (int) ceil($priceCeiling / 25) * 25),
            ];
        });
    }

    public function relatedCategories(Category $category, int $limit = 6): array
    {
        $query = Category::query()
            ->storefrontReachable()
            ->where('id', '!=', $category->id)
            ->withCount('products');

        if ($category->parent_id) {
            $query->where('parent_id', $category->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        $categories = $query->ordered()->limit($limit)->get();
        $this->attachProductCounts($categories);

        return $categories->map(fn (Category $item) => $this->categoryData($item))->all();
    }

    public function breadcrumbs(Category $category): Collection
    {
        return $this->treeService->breadcrumbs($category);
    }

    private function topLevelQuery(): Builder
    {
        return Category::query()
            ->storefrontReachable()
            ->whereNull('parent_id')
            ->ordered();
    }

    /** @return array<int> */
    private function categoryIdsForListing(Category $category): array
    {
        $categoryId = (int) $category->id;
        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.category-listing-ids.'.$categoryId.'.'.$cacheVersion;

        if (isset($this->runtimeListingCategoryIds[$categoryId])) {
            return $this->runtimeListingCategoryIds[$categoryId];
        }

        $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

        return $this->runtimeListingCategoryIds[$categoryId] = Cache::remember($cacheKey, $ttl, function () use ($category, $categoryId): array {
            $closureIds = $this->treeService->descendantIds($categoryId, true);
            $fallbackIds = $this->descendantIdsFromParentTree($categoryId);

            $categoryIds = collect([$categoryId])
                ->merge($closureIds)
                ->merge($fallbackIds)
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            /*
             * Menu clicks usually land on a parent category such as Bags,
             * Accessories, or Performance Apparel. Those parent rows often do not
             * have products assigned directly; products are assigned to child/leaf
             * categories. If we only use the parent id, the page looks empty from
             * the menu while the same products appear after entering through a
             * product's leaf-category link.
             *
             * Leaf categories still respect the Include child products setting.
             * Parent categories always include descendants so menu category pages
             * behave like real storefront landing pages.
             */
            if (! $category->include_descendant_products && count($categoryIds) <= 1) {
                $categoryIds = [$categoryId];
            }

            return Category::query()
                ->whereIn('id', $categoryIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->whenEmpty(fn (Collection $ids): Collection => collect([$categoryId]))
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * Fallback for imported/category-edited data when category_closure has not
     * been rebuilt yet. Category pages should still find products assigned by
     * category_id, subcategory_id, or category_product while the admin rebuilds
     * the closure table/cache.
     *
     * @return array<int>
     */
    private function descendantIdsFromParentTree(int $categoryId): array
    {
        $byParent = $this->categoryParentRows()
            ->groupBy(fn (array $category): int => (int) ($category['parent_id'] ?? 0));

        $ids = [];
        $visited = [];

        $walk = function (int $id) use (&$walk, &$ids, &$visited, $byParent): void {
            if (isset($visited[$id])) {
                return;
            }

            $visited[$id] = true;
            $ids[] = $id;

            foreach (($byParent->get($id) ?? collect()) as $child) {
                $walk((int) $child['id']);
            }
        };

        $walk($categoryId);

        return $ids;
    }

    /** @return Collection<int, array{id:int,parent_id:int|null}> */
    private function categoryParentRows(): Collection
    {
        if ($this->runtimeCategoryParentRows !== null) {
            return $this->runtimeCategoryParentRows;
        }

        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.category-parent-rows.'.$cacheVersion;
        $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

        $rows = Cache::remember($cacheKey, $ttl, fn (): array => Category::query()
            ->get(['id', 'parent_id'])
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'parent_id' => $category->parent_id === null ? null : (int) $category->parent_id,
            ])
            ->all());

        return $this->runtimeCategoryParentRows = collect($rows);
    }

    private function productCount(Category $category): int
    {
        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.category-product-count.'.$category->id.'.'.$cacheVersion;
        $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

        return (int) Cache::remember($cacheKey, $ttl, fn (): int => $this->applyCategoryProductFilter(
            Product::query()->published(),
            $this->categoryIdsForListing($category)
        )->count());
    }

    /** @param Collection<int, Category> $categories */
    private function attachProductCounts(Collection $categories): void
    {
        foreach ($categories as $category) {
            $category->setAttribute('products_count', $this->productCount($category));
        }
    }

    /** @param array<int> $categoryIds */
    private function filterableAttributeSlugsForListing(Category $category, array $categoryIds): array
    {
        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.category-filterable-slugs.'.$category->id.'.'.$cacheVersion;
        $ttl = max(60, (int) config('catalog.facets_cache_seconds', 300));

        return Cache::remember($cacheKey, $ttl, function () use ($category, $categoryIds): array {
            $configured = $category->filters
                ->filter(fn (CatalogAttribute $attribute): bool => $attribute->is_active && $attribute->is_filterable)
                ->pluck('slug');

            $listing = $this->productListingSubquery($categoryIds);

            $dynamic = DB::table('attributes as a')
                ->join('attribute_values as av', 'av.attribute_id', '=', 'a.id')
                ->join('attribute_value_product as avp', 'avp.attribute_value_id', '=', 'av.id')
                ->joinSub($listing, 'category_listing', fn ($join) => $join->on('category_listing.product_id', '=', 'avp.product_id'))
                ->join('products as p', 'p.id', '=', 'avp.product_id')
                ->where('p.status', 'active')
                ->where('p.is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('p.published_at')->orWhere('p.published_at', '<=', now());
                })
                ->where('a.is_active', true)
                ->where('a.is_filterable', true)
                ->whereNull('a.deleted_at')
                ->where('av.is_active', true)
                ->pluck('a.slug');

            return $configured
                ->merge($dynamic)
                ->map(fn ($slug): string => (string) $slug)
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    /** @param array<int> $categoryIds */
    private function productListingSubquery(array $categoryIds)
    {
        $categoryIds = collect($categoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $pivotListing = DB::table('category_product')
            ->selectRaw('product_id, MAX(CASE WHEN is_featured THEN 1 ELSE 0 END) AS category_featured, MIN(sort_order) AS category_sort')
            ->whereIn('category_id', $categoryIds)
            ->groupBy('product_id');

        $legacyListing = DB::table('products')
            ->selectRaw('id AS product_id, 0 AS category_featured, COALESCE(sort_order, 999999) AS category_sort')
            ->where(function ($query) use ($categoryIds): void {
                $query->whereIn('category_id', $categoryIds)
                    ->orWhereIn('subcategory_id', $categoryIds);
            });

        return DB::query()
            ->fromSub($pivotListing->unionAll($legacyListing), 'category_listing_source')
            ->selectRaw('product_id, MAX(category_featured) AS category_featured, MIN(category_sort) AS category_sort')
            ->groupBy('product_id');
    }

    /** @param array<int> $categoryIds */
    private function categoryFeaturedSelect(array $categoryIds)
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        return DB::table('category_product')
            ->selectRaw('COALESCE(MAX(CASE WHEN is_featured THEN 1 ELSE 0 END), 0)')
            ->whereColumn('category_product.product_id', 'products.id')
            ->whereIn('category_product.category_id', $categoryIds);
    }

    /** @param array<int> $categoryIds */
    private function categorySortSelect(array $categoryIds)
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        return DB::table('category_product')
            ->selectRaw('MIN(category_product.sort_order)')
            ->whereColumn('category_product.product_id', 'products.id')
            ->whereIn('category_product.category_id', $categoryIds);
    }

    /** @param array<int> $categoryIds */
    private function applyCategoryProductFilter(Builder $query, array $categoryIds): Builder
    {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        if ($categoryIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder) use ($categoryIds): void {
            $builder->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds))
                ->orWhereIn('products.category_id', $categoryIds)
                ->orWhereIn('products.subcategory_id', $categoryIds);
        });
    }

    /**
     * @param  array<int>  $categoryIds
     * @return array<int>
     */
    private function normalizeCategoryIds(array $categoryIds): array
    {
        return collect($categoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
