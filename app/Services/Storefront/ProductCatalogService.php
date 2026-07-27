<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFabricPriceTable;
use App\Support\PriceTableShipping;
use App\Support\ProductRoster;
use App\Support\ProductSizing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as ArrayLengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCatalogService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $hydratedProducts = null;

    /** @var array<int, array<int, array<string, mixed>>> */
    private array $featuredProducts = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private array $latestProducts = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private array $bestSellingProducts = [];

    /** @var array<int, array<int>> */
    private array $runtimeExpandedCategoryFilterIds = [];

    private ?\Illuminate\Support\Collection $runtimeCategoryFilterParentRows = null;

    public function all(): array
    {
        if ($this->hydratedProducts !== null) {
            return $this->hydratedProducts;
        }

        if (Schema::hasTable('products') && Product::query()->published()->exists()) {
            $cacheVersion = $this->catalogCacheVersionSuffix();
            $cacheKey = 'catalog.product-summaries.'.$cacheVersion;
            $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

            return $this->hydratedProducts = Cache::remember($cacheKey, $ttl, fn (): array => Product::query()
                ->published()
                ->with($this->listingRelations())
                ->orderBy('sort_order')
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (Product $product): array => $this->fromListingModel($product))
                ->values()
                ->all());
        }

        return $this->hydratedProducts = collect($this->products())
            ->map(fn (array $product): array => $this->hydrateProduct($product))
            ->values()
            ->all();
    }

    public function search(?string $query = null, ?string $tag = null, ?array $categoryIds = null): array
    {
        return $this->filteredFallbackProducts($query, $tag, $categoryIds ?? [])->values()->all();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function searchPaginated(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $this->listingPageSize($perPage);
        $filters['categories'] = $this->normalizeCategoryFilterIds($filters['categories'] ?? []);
        $filters['sports'] = $this->normalizeCategoryFilterIds($filters['sports'] ?? []);

        if (Schema::hasTable('products') && Product::query()->published()->exists()) {
            $products = Product::query()
                ->published()
                ->with($this->listingRelations());

            $this->applyProductSearchFilters($products, $filters['q'] ?? null, $filters['tag'] ?? null);
            $this->applyProductCategoryFilters($products, $filters['categories']);
            $this->applyProductCategoryFilters($products, $filters['sports']);
            $this->applyCommonCatalogFilters($products, $filters);
            $this->applyProductListingSort($products, (string) ($filters['sort'] ?? 'featured'));

            $paginator = $products->paginate($perPage)->withQueryString();
            $paginator->through(fn (Product $product): array => $this->fromListingModel($product));

            return $paginator;
        }

        return $this->paginateArray(
            $this->filteredFallbackProducts(
                $filters['q'] ?? null,
                $filters['tag'] ?? null,
                array_merge($filters['categories'], $filters['sports'])
            ),
            $perPage
        );
    }

    public function suggestions(string $query, int $limit = 8): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 12));

        if ($query === '') {
            return [];
        }

        if (Schema::hasTable('products') && Product::query()->published()->exists()) {
            $products = Product::query()
                ->published()
                ->with($this->suggestionRelations());

            $this->applyProductSearchFilters($products, $query, null);
            $this->applyProductListingSort($products);

            return $products
                ->limit($limit)
                ->get()
                ->map(fn (Product $product): array => $this->fromSuggestionModel($product))
                ->values()
                ->all();
        }

        return $this->filteredFallbackProducts($query, null)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function filteredFallbackProducts(?string $query = null, ?string $tag = null, array $categoryIds = []): \Illuminate\Support\Collection
    {
        $products = collect($this->all());
        $selectedCategoryIds = $this->normalizeCategoryFilterIds($categoryIds);
        $selectedCategoryLabels = $this->labelsForCategoryIds($selectedCategoryIds);

        if ($selectedCategoryLabels !== []) {
            $products = $products->filter(function (array $product) use ($selectedCategoryLabels): bool {
                $productLabels = collect([
                    $product['category'] ?? null,
                    $product['subcategory'] ?? null,
                    $product['category_slug'] ?? null,
                    $product['subcategory_slug'] ?? null,
                    $product['sport'] ?? null,
                ])->merge(collect($product['categories'] ?? [])->flatMap(fn ($category) => [
                    $category['name'] ?? null,
                    $category['slug'] ?? null,
                ]))->filter()->map(fn ($value): string => Str::lower(trim((string) $value)));

                return $productLabels->intersect($selectedCategoryLabels)->isNotEmpty();
            });
        }

        if (filled($tag)) {
            $tagNeedle = Str::lower(trim((string) $tag));

            $products = $products->filter(function (array $product) use ($tagNeedle): bool {
                return collect($product['tags'] ?? [])
                    ->map(fn ($productTag) => Str::lower(trim((string) $productTag)))
                    ->contains($tagNeedle);
            });
        }

        if (filled($query)) {
            $needle = Str::lower((string) $query);

            $products = $products->filter(function (array $product) use ($needle): bool {
                return Str::contains(Str::lower($product['title'] ?? ''), $needle)
                    || Str::contains(Str::lower($product['short_title'] ?? ''), $needle)
                    || Str::contains(Str::lower($product['category'] ?? ''), $needle)
                    || Str::contains(Str::lower($product['subcategory'] ?? ''), $needle)
                    || Str::contains(Str::lower(implode(' ', $product['tags'] ?? [])), $needle)
                    || Str::contains(Str::lower($product['sport'] ?? ''), $needle)
                    || Str::contains(Str::lower($product['sku'] ?? ''), $needle);
            });
        }

        return $products->values();
    }

    /**
     * Builds the parent → child category filter used on the all-products page.
     *
     * @param array<int, int|string> $selectedCategoryIds
     * @return array<int, array<string, mixed>>
     */
    public function categoryFilterTree(array $selectedCategoryIds = []): array
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return [];
        }

        $selectedCategoryIds = $this->normalizeCategoryFilterIds($selectedCategoryIds);
        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.products.category-filter-tree.icon-v4.'.$this->catalogCacheVersionSuffix();
        $ttl = max(60, (int) config('catalog.facets_cache_seconds', 300));

        $tree = Cache::remember($cacheKey, $ttl, function (): array {
            $parents = Category::query()
                ->storefrontReachable()
                ->whereNull('parent_id')
                ->where('category_type', '!=', 'sport')
                ->with(['children' => fn ($query) => $query->storefrontReachable()->ordered()])
                ->ordered()
                ->get();

            return $parents
                ->map(function (Category $parent): array {
                    $children = $parent->children
                        ->map(function (Category $child): array {
                            return [
                                'id' => (int) $child->id,
                                'label' => $child->name,
                                'slug' => $child->slug,
                                'count' => $this->countProductsForCategoryFilter((int) $child->id),
                            ];
                        })
                        ->filter(fn (array $child): bool => $child['count'] > 0)
                        ->values()
                        ->all();

                    return [
                        'id' => (int) $parent->id,
                        'label' => $parent->name,
                        'slug' => $parent->slug,
                        'icon_url' => $parent->uploadedIconUrl(),
                        'count' => $this->countProductsForCategoryFilter((int) $parent->id),
                        'children' => $children,
                    ];
                })
                ->filter(fn (array $parent): bool => $parent['count'] > 0 || $parent['children'] !== [])
                ->values()
                ->all();
        });

        return collect($tree)
            ->map(function (array $parent) use ($selectedCategoryIds): array {
                $parent['selected'] = in_array((int) $parent['id'], $selectedCategoryIds, true);
                $parent['children'] = collect($parent['children'] ?? [])
                    ->map(function (array $child) use ($selectedCategoryIds): array {
                        $child['selected'] = in_array((int) $child['id'], $selectedCategoryIds, true);

                        return $child;
                    })
                    ->values()
                    ->all();
                $parent['has_selected_child'] = collect($parent['children'])->contains(fn (array $child): bool => (bool) ($child['selected'] ?? false));

                return $parent;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, int|string> $categoryIds
     * @return array<int>
     */
    public function normalizeCategoryFilterIds(array $categoryIds): array
    {
        $ids = collect($categoryIds)
            ->flatMap(function ($value): array {
                if (is_array($value)) {
                    return $value;
                }

                return explode(',', (string) $value);
            })
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] || ! Schema::hasTable('categories')) {
            return $ids;
        }

        return Category::query()
            ->storefrontReachable()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $selectedCategoryIds
     */
    private function applyProductCategoryFilters(Builder $products, array $selectedCategoryIds): void
    {
        if ($selectedCategoryIds === []) {
            return;
        }

        $filterCategoryIds = $this->expandedCategoryFilterIds($selectedCategoryIds);

        if ($filterCategoryIds === []) {
            return;
        }

        $this->whereProductsMatchAnyCategory($products, $filterCategoryIds);
    }

    private function countProductsForCategoryFilter(int $categoryId): int
    {
        $categoryIds = $this->expandedCategoryFilterIds([$categoryId]);

        if ($categoryIds === []) {
            return 0;
        }

        $query = Product::query()->published();
        $this->whereProductsMatchAnyCategory($query, $categoryIds);

        return (int) $query
            ->select('products.id')
            ->distinct()
            ->count('products.id');
    }

    /**
     * Keeps the category-filter product query and the category-count query identical.
     * A product can belong through the legacy category_id/subcategory_id columns
     * or through the category_product pivot table, so every path must be checked.
     *
     * @param array<int, int> $categoryIds
     */
    private function whereProductsMatchAnyCategory(Builder $products, array $categoryIds): Builder
    {
        $categoryIds = collect($categoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($categoryIds === []) {
            return $products->whereRaw('1 = 0');
        }

        return $products->where(function (Builder $builder) use ($categoryIds): void {
            $builder->whereIn('products.category_id', $categoryIds)
                ->orWhereIn('products.subcategory_id', $categoryIds);

            if (Schema::hasTable('category_product')) {
                $builder->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds));
            }
        });
    }

    /**
     * @param array<int, int> $categoryIds
     * @return array<int>
     */
    private function expandedCategoryFilterIds(array $categoryIds): array
    {
        $ids = collect($categoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] || ! Schema::hasTable('categories')) {
            return $ids;
        }

        if (count($ids) === 1 && isset($this->runtimeExpandedCategoryFilterIds[$ids[0]])) {
            return $this->runtimeExpandedCategoryFilterIds[$ids[0]];
        }

        $expanded = collect($ids);

        if (Schema::hasTable('category_closure')) {
            $expanded = $expanded->merge(DB::table('category_closure')
                ->whereIn('ancestor_id', $ids)
                ->pluck('descendant_id')
                ->map(fn ($id): int => (int) $id));
        }

        $parentRows = $this->categoryFilterParentRows();
        $frontier = $ids;

        do {
            $children = $parentRows
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->diff($expanded)
                ->values()
                ->all();

            if ($children === []) {
                break;
            }

            $expanded = $expanded->merge($children);
            $frontier = $children;
        } while (true);

        $expandedIds = $expanded
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($expandedIds !== []) {
            $expandedIds = Category::query()
                ->storefrontReachable()
                ->whereIn('id', $expandedIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if (count($ids) === 1) {
            $this->runtimeExpandedCategoryFilterIds[$ids[0]] = $expandedIds;
        }

        return $expandedIds;
    }

    private function categoryFilterParentRows(): \Illuminate\Support\Collection
    {
        if ($this->runtimeCategoryFilterParentRows !== null) {
            return $this->runtimeCategoryFilterParentRows;
        }

        $cacheKey = 'catalog.products.category-filter-parent-rows.'.$this->catalogCacheVersionSuffix();
        $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

        $rows = Cache::remember($cacheKey, $ttl, fn (): array => Category::query()
            ->storefrontReachable()
            ->select(['id', 'parent_id'])
            ->get()
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'parent_id' => $category->parent_id ? (int) $category->parent_id : null,
            ])
            ->all());

        return $this->runtimeCategoryFilterParentRows = collect($rows);
    }

    /**
     * @param array<int, int> $categoryIds
     * @return array<int, string>
     */
    private function labelsForCategoryIds(array $categoryIds): array
    {
        if ($categoryIds === [] || ! Schema::hasTable('categories')) {
            return [];
        }

        return Category::query()
            ->storefrontReachable()
            ->whereIn('id', $this->expandedCategoryFilterIds($categoryIds))
            ->get(['name', 'slug'])
            ->flatMap(fn (Category $category): array => [$category->name, $category->slug])
            ->filter()
            ->map(fn ($label): string => Str::lower(trim((string) $label)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build the complete, category-aware filter data used by the All Products page.
     * Production-time and shipping-time facets are intentionally excluded.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function filterOptions(array $filters): array
    {
        $categoryIds = $this->normalizeCategoryFilterIds($filters['categories'] ?? []);
        $sportIds = $this->normalizeCategoryFilterIds($filters['sports'] ?? []);
        $queryText = trim((string) ($filters['q'] ?? ''));
        $tag = trim((string) ($filters['tag'] ?? ''));
        $version = $this->catalogCacheVersionSuffix();
        $scopeKey = sha1(json_encode([$categoryIds, $sportIds, Str::lower($queryText), Str::lower($tag)], JSON_THROW_ON_ERROR));
        $ttl = max(60, (int) config('catalog.facets_cache_seconds', 300));

        $shared = Cache::remember(
            'catalog.products.complete-filter-options.'.$version.'.'.$scopeKey,
            $ttl,
            function () use ($categoryIds, $sportIds, $queryText, $tag): array {
                $categoryScoped = Product::query()->published();
                $this->applyProductSearchFilters($categoryScoped, $queryText, $tag);
                $this->applyProductCategoryFilters($categoryScoped, $categoryIds);

                $sports = $this->sportFilterOptions($categoryScoped);

                $facetScoped = clone $categoryScoped;
                $this->applyProductCategoryFilters($facetScoped, $sportIds);

                return array_merge(
                    ['sports' => $sports],
                    $this->commonFilterOptions($facetScoped)
                );
            }
        );

        $selectedSports = collect($sportIds);
        $shared['sports'] = collect($shared['sports'] ?? [])->map(function (array $sport) use ($selectedSports): array {
            $sport['selected'] = $selectedSports->contains((int) $sport['id']);

            return $sport;
        })->values()->all();

        return array_merge([
            'categories' => $this->categoryFilterTree($categoryIds),
        ], $shared);
    }

    /**
     * Apply the selected category/sport ids to a query. Category descendants are
     * included so a parent category behaves like a real storefront department.
     *
     * @param array<int, int|string> $categoryIds
     */
    public function applyCategorySelection(Builder $query, array $categoryIds): Builder
    {
        $this->applyProductCategoryFilters($query, $this->normalizeCategoryFilterIds($categoryIds));

        return $query;
    }

    /**
     * Apply every shared catalog facet except category/subcategory, search, and sort.
     *
     * @param array<string, mixed> $filters
     * @param array<int, string>|null $allowedAttributeSlugs
     */
    public function applyCommonCatalogFilters(Builder $query, array $filters, ?array $allowedAttributeSlugs = null): Builder
    {
        $productTypes = collect($filters['product_types'] ?? [])->filter()->unique()->values()->all();
        if ($productTypes !== []) {
            $query->whereIn('products.product_type', $productTypes);
        }


        foreach (['color' => 'colors', 'material' => 'materials'] as $kind => $filterKey) {
            $tokens = collect($filters[$filterKey] ?? [])->filter()->unique()->values()->all();
            if ($tokens === []) {
                continue;
            }

            $resolved = $this->resolveOptionFacetIds($kind, $tokens);
            $attributeValueIds = $resolved['attribute_value_ids'];
            $optionValueIds = $resolved['option_value_ids'];

            if ($attributeValueIds === [] && $optionValueIds === []) {
                $query->whereRaw('1 = 0');
                continue;
            }

            $query->where(function (Builder $builder) use ($attributeValueIds, $optionValueIds): void {
                if ($attributeValueIds !== []) {
                    $builder->whereHas('attributeValues', fn (Builder $attributeQuery) => $attributeQuery
                        ->whereIn('attribute_values.id', $attributeValueIds));
                }

                if ($optionValueIds !== []) {
                    $method = $attributeValueIds === [] ? 'whereHas' : 'orWhereHas';
                    $builder->{$method}('optionGroups', fn (Builder $groupQuery) => $groupQuery
                        ->where('product_option_groups.is_active', true)
                        ->whereHas('values', fn (Builder $valueQuery) => $valueQuery
                            ->whereIn('product_option_values.id', $optionValueIds)
                            ->where('product_option_values.is_active', true)));
                }
            });
        }

        $artworkMethodTokens = collect($filters['artwork_methods'] ?? [])->filter()->unique()->values()->all();
        if ($artworkMethodTokens !== []) {
            $artworkMethodIds = $this->resolveArtworkMethodIds($artworkMethodTokens);
            if ($artworkMethodIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('artworkMethods', fn (Builder $builder) => $builder
                    ->whereIn('product_artwork_methods.id', $artworkMethodIds)
                    ->where('product_artwork_methods.is_active', true));
            }
        }

        foreach ((array) ($filters['attributes'] ?? []) as $attributeSlug => $valueSlugs) {
            if (in_array($attributeSlug, ['production-time', 'shipping-time', 'free-shipping', 'free-setup', 'brand'], true)) {
                continue;
            }

            if ($allowedAttributeSlugs !== null && ! in_array($attributeSlug, $allowedAttributeSlugs, true)) {
                continue;
            }

            $valueSlugs = collect($valueSlugs)->filter()->unique()->values()->all();
            if ($valueSlugs === []) {
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

        $listingPriceExpression = $this->listingPriceExpression();
        if (($filters['min_price'] ?? null) !== null) {
            $query->whereRaw($listingPriceExpression.' >= ?', [(float) $filters['min_price']]);
        }
        if (($filters['max_price'] ?? null) !== null) {
            $query->whereRaw($listingPriceExpression.' <= ?', [(float) $filters['max_price']]);
        }

        $this->applyMoqFilter($query, $filters['moq'] ?? []);
        $this->applyCustomizationFilter($query, $filters['customization'] ?? []);
        $this->applyAvailabilityFilter($query, $filters['availability'] ?? []);

        if (($filters['min_rating'] ?? null) !== null) {
            $query->whereNotNull('products.rating_average')
                ->where('products.rating_average', '>=', (int) $filters['min_rating'])
                ->where('products.reviews_count', '>', 0);
        }

        return $query;
    }

    /**
     * Return common facet options for a product listing scope.
     *
     * @return array<string, mixed>
     */
    public function commonFilterOptions(Builder $baseQuery): array
    {
        $products = (clone $baseQuery)
            ->select([
                'products.id', 'products.product_type', 'products.base_price', 'products.minimum_quantity',
                'products.is_customizable', 'products.artwork_upload_enabled', 'products.jersey_roster_enabled',
                'products.track_inventory', 'products.stock_quantity', 'products.allow_backorder',
                'products.rating_average', 'products.reviews_count',
            ])
            ->selectRaw($this->listingPriceExpression().' AS catalog_filter_price')
            ->reorder()
            ->get();

        $listing = $this->listingIdSubquery($baseQuery);

        $productTypes = $products
            ->filter(fn (Product $product): bool => filled($product->product_type))
            ->groupBy(fn (Product $product): string => trim((string) $product->product_type))
            ->map(fn (Collection $items, string $label): array => [
                'value' => $label,
                'label' => Str::headline($label),
                'count' => $items->pluck('id')->unique()->count(),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        [$colors, $materials] = $this->visualFacetOptions($listing);
        $artworkMethods = $this->artworkFacetOptions($listing);
        $attributes = $this->genericAttributeFacetOptions($listing);

        $moqDefinitions = [
            'single' => ['1 piece', fn (int $quantity): bool => $quantity <= 1],
            '2-5' => ['2–5 pieces', fn (int $quantity): bool => $quantity >= 2 && $quantity <= 5],
            '6-11' => ['6–11 pieces', fn (int $quantity): bool => $quantity >= 6 && $quantity <= 11],
            '12-24' => ['12–24 pieces', fn (int $quantity): bool => $quantity >= 12 && $quantity <= 24],
            '25-49' => ['25–49 pieces', fn (int $quantity): bool => $quantity >= 25 && $quantity <= 49],
            '50-plus' => ['50+ pieces', fn (int $quantity): bool => $quantity >= 50],
        ];

        $moq = collect($moqDefinitions)->map(function (array $definition, string $value) use ($products): array {
            [$label, $matches] = $definition;

            return [
                'value' => $value,
                'label' => $label,
                'count' => $products->filter(fn (Product $product): bool => $matches((int) $product->minimum_quantity))->count(),
            ];
        })->filter(fn (array $option): bool => $option['count'] > 0)->values()->all();

        $customization = [
            ['value' => 'customizable', 'label' => 'Customizable', 'count' => $products->where('is_customizable', true)->count()],
            ['value' => 'ready-made', 'label' => 'Ready-made / standard', 'count' => $products->where('is_customizable', false)->count()],
            ['value' => 'artwork-upload', 'label' => 'Artwork upload available', 'count' => $products->where('artwork_upload_enabled', true)->count()],
            ['value' => 'player-details', 'label' => 'Player names & numbers', 'count' => $products->where('jersey_roster_enabled', true)->count()],
        ];
        $customization = collect($customization)->filter(fn (array $option): bool => $option['count'] > 0)->values()->all();

        $availability = [
            [
                'value' => 'in-stock',
                'label' => 'In stock',
                'count' => $products->filter(fn (Product $product): bool => (bool) $product->track_inventory && (int) $product->stock_quantity > 0)->count(),
            ],
            [
                'value' => 'backorder',
                'label' => 'Backorder available',
                'count' => $products->filter(fn (Product $product): bool => (bool) $product->allow_backorder && (int) $product->stock_quantity <= 0)->count(),
            ],
            [
                'value' => 'made-to-order',
                'label' => 'Made to order',
                'count' => $products->where('track_inventory', false)->count(),
            ],
        ];
        $availability = collect($availability)->filter(fn (array $option): bool => $option['count'] > 0)->values()->all();

        $ratingOptions = collect([4, 3, 2])->map(fn (int $rating): array => [
            'value' => $rating,
            'label' => $rating.' stars & up',
            'count' => $products->filter(fn (Product $product): bool => is_numeric($product->rating_average)
                && (float) $product->rating_average >= $rating
                && (int) $product->reviews_count > 0)->count(),
        ])->filter(fn (array $option): bool => $option['count'] > 0)->values()->all();

        $prices = $products
            ->map(fn (Product $product) => $product->catalog_filter_price ?? $product->base_price)
            ->filter(fn ($price): bool => is_numeric($price))
            ->map(fn ($price): float => (float) $price);
        $priceFloor = $prices->isEmpty() ? 0 : max(0, (int) floor($prices->min()));
        $priceCeilingRaw = $prices->isEmpty() ? 100 : (float) $prices->max();
        $priceCeiling = max(25, (int) ceil($priceCeilingRaw / 25) * 25);

        return [
            'product_types' => $productTypes,
            'colors' => $colors,
            'materials' => $materials,
            'artwork_methods' => $artworkMethods,
            'attributes' => $attributes,
            'price_floor' => $priceFloor,
            'price_ceiling' => $priceCeiling,
            'moq' => $moq,
            'customization' => $customization,
            'availability' => $availability,
            'rating_options' => $ratingOptions,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function sportFilterOptions(Builder $baseQuery): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        return Category::query()
            ->storefrontReachable()
            ->whereNull('parent_id')
            ->where('category_type', 'sport')
            ->ordered()
            ->get()
            ->map(function (Category $category) use ($baseQuery): array {
                $query = clone $baseQuery;
                $this->whereProductsMatchAnyCategory($query, $this->expandedCategoryFilterIds([(int) $category->id]));

                return [
                    'id' => (int) $category->id,
                    'label' => $category->name,
                    'slug' => $category->slug,
                    'count' => (int) $query->distinct('products.id')->count('products.id'),
                ];
            })
            ->filter(fn (array $sport): bool => $sport['count'] > 0)
            ->values()
            ->all();
    }

    private function listingIdSubquery(Builder $baseQuery): Builder
    {
        $listing = clone $baseQuery;
        $listing->setEagerLoads([]);

        return $listing->select('products.id')->reorder()->distinct();
    }

    /** @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>} */
    private function sizeFacetOptions(Builder $listing): array
    {
        if (! Schema::hasTable('product_size_groups') || ! Schema::hasTable('product_sizes')) {
            return [[], []];
        }

        $rows = DB::table('product_size_groups as psg')
            ->joinSub(clone $listing, 'size_listing', fn ($join) => $join->on('size_listing.id', '=', 'psg.product_id'))
            ->leftJoin('product_sizes as ps', function ($join): void {
                $join->on('ps.product_size_group_id', '=', 'psg.id')->where('ps.is_active', true);
            })
            ->where('psg.is_active', true)
            ->select(['psg.product_id', 'psg.id as group_id', 'psg.name as group_name', 'psg.code as group_code', 'ps.id as size_id', 'ps.label as size_label', 'ps.code as size_code'])
            ->get();

        $audiences = $rows
            ->groupBy(fn ($row): string => Str::slug((string) ($row->group_code ?: $row->group_name)))
            ->map(function (Collection $items, string $token): array {
                $first = $items->first();

                return [
                    'value' => $token,
                    'label' => (string) $first->group_name,
                    'count' => $items->pluck('product_id')->unique()->count(),
                ];
            })
            ->filter(fn (array $item): bool => $item['value'] !== '' && $item['count'] > 0)
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $sizes = $rows
            ->filter(fn ($row): bool => $row->size_id !== null && filled($row->size_label))
            ->groupBy(fn ($row): string => Str::slug((string) ($row->size_code ?: $row->size_label)))
            ->map(function (Collection $items, string $token): array {
                $first = $items->first();

                return [
                    'value' => $token,
                    'label' => (string) $first->size_label,
                    'count' => $items->pluck('product_id')->unique()->count(),
                ];
            })
            ->filter(fn (array $item): bool => $item['value'] !== '' && $item['count'] > 0)
            ->sortBy(function (array $item): string {
                $knownSizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
                $position = array_search(strtoupper($item['label']), $knownSizes, true);
                $position = $position === false ? 999 : $position;

                return str_pad((string) $position, 3, '0', STR_PAD_LEFT).$item['label'];
            })
            ->values()
            ->all();

        return [$audiences, $sizes];
    }

    /** @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>} */
    private function visualFacetOptions(Builder $listing): array
    {
        $attributeRows = collect();
        if (Schema::hasTable('attribute_value_product') && Schema::hasTable('attribute_values') && Schema::hasTable('attributes')) {
            $attributeRows = DB::table('attribute_value_product as avp')
                ->joinSub(clone $listing, 'attribute_listing', fn ($join) => $join->on('attribute_listing.id', '=', 'avp.product_id'))
                ->join('attribute_values as av', 'av.id', '=', 'avp.attribute_value_id')
                ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                ->where('a.is_active', true)
                ->where('a.is_filterable', true)
                ->where('av.is_active', true)
                ->whereNull('a.deleted_at')
                ->select([
                    'avp.product_id', 'av.id as value_id', 'av.label', 'av.slug as value_code', 'av.color_hex',
                    'a.name as group_name', 'a.slug as group_code',
                ])
                ->get()
                ->map(fn ($row) => (object) array_merge((array) $row, ['source' => 'attribute']));
        }

        $optionRows = collect();
        if (Schema::hasTable('product_option_groups') && Schema::hasTable('product_option_values')) {
            $optionRows = DB::table('product_option_groups as pog')
                ->joinSub(clone $listing, 'option_listing', fn ($join) => $join->on('option_listing.id', '=', 'pog.product_id'))
                ->join('product_option_values as pov', 'pov.product_option_group_id', '=', 'pog.id')
                ->where('pog.is_active', true)
                ->where('pov.is_active', true)
                ->select([
                    'pog.product_id', 'pov.id as value_id', 'pov.label', 'pov.code as value_code', 'pov.color_hex',
                    'pog.name as group_name', 'pog.code as group_code', 'pog.jersey_customization_type',
                ])
                ->get()
                ->map(fn ($row) => (object) array_merge((array) $row, ['source' => 'option']));
        }

        $rows = $attributeRows->concat($optionRows);
        $build = function (string $kind) use ($rows): array {
            return $rows
                ->filter(fn ($row): bool => $this->facetGroupMatches(
                    $kind,
                    (string) ($row->group_name ?? ''),
                    (string) ($row->group_code ?? ''),
                    (string) ($row->jersey_customization_type ?? '')
                ))
                ->groupBy(fn ($row): string => Str::slug((string) (($row->value_code ?? '') ?: ($row->label ?? ''))))
                ->map(function (Collection $items, string $token): array {
                    $first = $items->first();
                    $hex = $items->pluck('color_hex')->filter()->first();

                    return [
                        'value' => $token,
                        'label' => (string) $first->label,
                        'color_hex' => $hex ? (string) $hex : null,
                        'count' => $items->pluck('product_id')->unique()->count(),
                    ];
                })
                ->filter(fn (array $item): bool => $item['value'] !== '' && $item['count'] > 0)
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        };

        return [$build('color'), $build('material')];
    }

    /** @return array<int, array<string, mixed>> */
    private function artworkFacetOptions(Builder $listing): array
    {
        if (! Schema::hasTable('product_artwork_methods')) {
            return [];
        }

        return DB::table('product_artwork_methods as pam')
            ->joinSub(clone $listing, 'artwork_listing', fn ($join) => $join->on('artwork_listing.id', '=', 'pam.product_id'))
            ->where('pam.is_active', true)
            ->select(['pam.product_id', 'pam.name', 'pam.code'])
            ->get()
            ->groupBy(fn ($row): string => Str::slug((string) ($row->code ?: $row->name)))
            ->map(function (Collection $items, string $token): array {
                $first = $items->first();

                return [
                    'value' => $token,
                    'label' => (string) $first->name,
                    'count' => $items->pluck('product_id')->unique()->count(),
                ];
            })
            ->filter(fn (array $item): bool => $item['value'] !== '' && $item['count'] > 0)
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function genericAttributeFacetOptions(Builder $listing): array
    {
        if (! Schema::hasTable('attribute_value_product') || ! Schema::hasTable('attribute_values') || ! Schema::hasTable('attributes')) {
            return [];
        }

        $blocked = ['production-time', 'shipping-time', 'free-shipping', 'free-setup', 'brand', 'color', 'size'];

        return DB::table('attribute_value_product as avp')
            ->joinSub(clone $listing, 'generic_attribute_listing', fn ($join) => $join->on('generic_attribute_listing.id', '=', 'avp.product_id'))
            ->join('attribute_values as av', 'av.id', '=', 'avp.attribute_value_id')
            ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
            ->where('a.is_active', true)
            ->where('a.is_filterable', true)
            ->where('av.is_active', true)
            ->whereNull('a.deleted_at')
            ->select([
                'avp.product_id', 'a.id as attribute_id', 'a.name as attribute_name', 'a.slug as attribute_slug',
                'a.display_type', 'av.id as value_id', 'av.label as value_label', 'av.slug as value_slug',
                'av.color_hex', 'av.image_path', 'av.image_url',
            ])
            ->get()
            ->reject(function ($row) use ($blocked): bool {
                $slug = (string) $row->attribute_slug;

                return in_array($slug, $blocked, true)
                    || $this->facetGroupMatches('color', (string) $row->attribute_name, $slug)
                    || $this->facetGroupMatches('material', (string) $row->attribute_name, $slug);
            })
            ->groupBy('attribute_id')
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                $values = $rows->groupBy('value_id')->map(function (Collection $items): array {
                    $value = $items->first();

                    return [
                        'id' => (int) $value->value_id,
                        'label' => (string) $value->value_label,
                        'slug' => (string) $value->value_slug,
                        'color_hex' => $value->color_hex ? (string) $value->color_hex : null,
                        'image' => null,
                        'count' => $items->pluck('product_id')->unique()->count(),
                    ];
                })->filter(fn (array $value): bool => $value['count'] > 0)->values()->all();

                return [
                    'id' => (int) $first->attribute_id,
                    'name' => (string) $first->attribute_name,
                    'slug' => (string) $first->attribute_slug,
                    'display_type' => (string) $first->display_type,
                    'is_expanded' => false,
                    'values' => $values,
                ];
            })
            ->filter(fn (array $attribute): bool => $attribute['values'] !== [])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** @param array<int, string> $tokens */
    private function resolveSizeGroupIds(array $tokens): array
    {
        $tokens = collect($tokens)->map(fn ($value): string => Str::slug((string) $value))->filter()->unique();
        if ($tokens->isEmpty() || ! Schema::hasTable('product_size_groups')) {
            return [];
        }

        return DB::table('product_size_groups')
            ->where('is_active', true)
            ->get(['id', 'name', 'code'])
            ->filter(fn ($row): bool => $tokens->contains(Str::slug((string) ($row->code ?: $row->name))))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int, string> $tokens */
    private function resolveSizeIds(array $tokens): array
    {
        $tokens = collect($tokens)->map(fn ($value): string => Str::slug((string) $value))->filter()->unique();
        if ($tokens->isEmpty() || ! Schema::hasTable('product_sizes')) {
            return [];
        }

        return DB::table('product_sizes')
            ->where('is_active', true)
            ->get(['id', 'label', 'code'])
            ->filter(fn ($row): bool => $tokens->contains(Str::slug((string) ($row->code ?: $row->label))))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int, string> $tokens */
    private function resolveArtworkMethodIds(array $tokens): array
    {
        $tokens = collect($tokens)->map(fn ($value): string => Str::slug((string) $value))->filter()->unique();
        if ($tokens->isEmpty() || ! Schema::hasTable('product_artwork_methods')) {
            return [];
        }

        return DB::table('product_artwork_methods')
            ->where('is_active', true)
            ->get(['id', 'name', 'code'])
            ->filter(fn ($row): bool => $tokens->contains(Str::slug((string) ($row->code ?: $row->name))))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $tokens
     * @return array{attribute_value_ids:array<int,int>,option_value_ids:array<int,int>}
     */
    private function resolveOptionFacetIds(string $kind, array $tokens): array
    {
        $tokens = collect($tokens)->map(fn ($value): string => Str::slug((string) $value))->filter()->unique();
        $attributeIds = collect();
        $optionIds = collect();

        if ($tokens->isEmpty()) {
            return ['attribute_value_ids' => [], 'option_value_ids' => []];
        }

        if (Schema::hasTable('attribute_values') && Schema::hasTable('attributes')) {
            $attributeIds = DB::table('attribute_values as av')
                ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                ->where('a.is_active', true)
                ->where('a.is_filterable', true)
                ->where('av.is_active', true)
                ->whereNull('a.deleted_at')
                ->get(['av.id', 'av.label', 'av.slug', 'a.name as group_name', 'a.slug as group_code'])
                ->filter(fn ($row): bool => $this->facetGroupMatches($kind, (string) $row->group_name, (string) $row->group_code)
                    && $tokens->contains(Str::slug((string) ($row->slug ?: $row->label))))
                ->pluck('id');
        }

        if (Schema::hasTable('product_option_groups') && Schema::hasTable('product_option_values')) {
            $optionIds = DB::table('product_option_values as pov')
                ->join('product_option_groups as pog', 'pog.id', '=', 'pov.product_option_group_id')
                ->where('pog.is_active', true)
                ->where('pov.is_active', true)
                ->get([
                    'pov.id', 'pov.label', 'pov.code', 'pog.name as group_name', 'pog.code as group_code', 'pog.jersey_customization_type',
                ])
                ->filter(fn ($row): bool => $this->facetGroupMatches(
                    $kind,
                    (string) $row->group_name,
                    (string) $row->group_code,
                    (string) ($row->jersey_customization_type ?? '')
                ) && $tokens->contains(Str::slug((string) ($row->code ?: $row->label))))
                ->pluck('id');
        }

        return [
            'attribute_value_ids' => $attributeIds->map(fn ($id): int => (int) $id)->unique()->values()->all(),
            'option_value_ids' => $optionIds->map(fn ($id): int => (int) $id)->unique()->values()->all(),
        ];
    }

    private function facetGroupMatches(string $kind, string ...$haystacks): bool
    {
        $text = Str::lower(implode(' ', $haystacks));

        return match ($kind) {
            'color' => Str::contains($text, ['color', 'colour']),
            'material' => Str::contains($text, ['fabric', 'material', 'materials', 'metarial', 'meterial']),
            default => false,
        };
    }

    /** @param array<int, string> $ranges */
    private function applyMoqFilter(Builder $query, array $ranges): void
    {
        $ranges = collect($ranges)->filter()->unique()->values();
        if ($ranges->isEmpty()) {
            return;
        }

        $query->where(function (Builder $builder) use ($ranges): void {
            foreach ($ranges as $index => $range) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}(function (Builder $rangeQuery) use ($range): void {
                    match ($range) {
                        'single' => $rangeQuery->where('products.minimum_quantity', '<=', 1),
                        '2-5' => $rangeQuery->whereBetween('products.minimum_quantity', [2, 5]),
                        '6-11' => $rangeQuery->whereBetween('products.minimum_quantity', [6, 11]),
                        '12-24' => $rangeQuery->whereBetween('products.minimum_quantity', [12, 24]),
                        '25-49' => $rangeQuery->whereBetween('products.minimum_quantity', [25, 49]),
                        '50-plus' => $rangeQuery->where('products.minimum_quantity', '>=', 50),
                        default => $rangeQuery->whereRaw('1 = 0'),
                    };
                });
            }
        });
    }

    /** @param array<int, string> $options */
    private function applyCustomizationFilter(Builder $query, array $options): void
    {
        $options = collect($options)->filter()->unique()->values();
        if ($options->isEmpty()) {
            return;
        }

        $query->where(function (Builder $builder) use ($options): void {
            foreach ($options as $index => $option) {
                $method = $index === 0 ? 'where' : 'orWhere';
                match ($option) {
                    'customizable' => $builder->{$method}('products.is_customizable', true),
                    'ready-made' => $builder->{$method}('products.is_customizable', false),
                    'artwork-upload' => $builder->{$method}('products.artwork_upload_enabled', true),
                    'player-details' => $builder->{$method}('products.jersey_roster_enabled', true),
                    default => null,
                };
            }
        });
    }

    /** @param array<int, string> $options */
    private function applyAvailabilityFilter(Builder $query, array $options): void
    {
        $options = collect($options)->filter()->unique()->values();
        if ($options->isEmpty()) {
            return;
        }

        $query->where(function (Builder $builder) use ($options): void {
            foreach ($options as $index => $option) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}(function (Builder $availabilityQuery) use ($option): void {
                    match ($option) {
                        'in-stock' => $availabilityQuery->where('products.track_inventory', true)->where('products.stock_quantity', '>', 0),
                        'backorder' => $availabilityQuery->where('products.allow_backorder', true)->where('products.stock_quantity', '<=', 0),
                        'made-to-order' => $availabilityQuery->where('products.track_inventory', false),
                        default => $availabilityQuery->whereRaw('1 = 0'),
                    };
                });
            }
        });
    }

    private function applyProductSearchFilters(Builder $products, ?string $query = null, ?string $tag = null): void
    {
        if (filled($tag)) {
            $tagNeedle = trim((string) $tag);
            $tagLike = '%'.Str::lower($tagNeedle).'%';

            $products->where(function (Builder $builder) use ($tagNeedle, $tagLike): void {
                $builder->whereJsonContains('products.tags', $tagNeedle)
                    ->orWhere('products.tags', 'like', $tagLike)
                    ->orWhere('products.badge_label', 'like', $tagLike);
            });
        }

        if (filled($query)) {
            $needle = trim((string) $query);
            $like = '%'.$needle.'%';

            $products->where(function (Builder $builder) use ($like): void {
                $builder->where('products.name', 'like', $like)
                    ->orWhere('products.sku', 'like', $like)
                    ->orWhere('products.brand', 'like', $like)
                    ->orWhere('products.product_type', 'like', $like)
                    ->orWhere('products.short_description', 'like', $like)
                    ->orWhere('products.tags', 'like', $like)
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', $like))
                    ->orWhereHas('subcategory', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', $like))
                    ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('categories.name', 'like', $like));
            });
        }
    }

    private function applyProductListingSort(Builder $products, string $sort = 'featured'): void
    {
        match ($sort) {
            'price-low' => $this->applyListingPriceSort($products, 'asc'),
            'price-high' => $this->applyListingPriceSort($products, 'desc'),
            'newest' => $products->orderByDesc('products.published_at')->orderByDesc('products.id'),
            'name-asc' => $products->orderBy('products.name')->orderByDesc('products.id'),
            'rating-high' => $products
                ->orderByDesc('products.rating_average')
                ->orderByDesc('products.reviews_count')
                ->orderBy('products.name'),
            'best-selling' => $products
                ->orderByDesc('products.recent_orders_count')
                ->orderByDesc('products.is_featured')
                ->orderByDesc('products.published_at')
                ->orderBy('products.name'),
            default => $products
                ->orderBy('products.sort_order')
                ->orderByDesc('products.is_featured')
                ->orderByDesc('products.published_at')
                ->orderBy('products.name')
                ->orderByDesc('products.id'),
        };
    }

    public function applyListingPriceSort(Builder $products, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $products
            ->orderByRaw($this->listingPriceExpression().' '.$direction)
            ->orderBy('products.name');
    }

    private function listingPriceExpression(): string
    {
        if (! Schema::hasTable('product_price_tiers')) {
            return 'products.base_price';
        }

        return 'COALESCE((SELECT ppt.unit_price'
            .' FROM product_price_tiers AS ppt'
            .' WHERE ppt.product_id = products.id'
            .' ORDER BY ppt.minimum_quantity DESC, ppt.id DESC LIMIT 1), products.base_price)';
    }

    private function listingPageSize(?int $perPage = null): int
    {
        $perPage ??= (int) config('catalog.products_page_size', config('catalog.category_page_size', 24));

        return max(1, min($perPage, 60));
    }

    private function paginateArray(\Illuminate\Support\Collection $products, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $items = $products->forPage($page, $perPage)->values();

        return new ArrayLengthAwarePaginator($items, $products->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Load the complete product-builder payload used by the product page, cart
     * repricing, and checkout configuration validation.
     */
    public function findFullBySlug(string $slug): ?array
    {
        if (Schema::hasTable('products')) {
            $query = Product::query()->with($this->fullProductRelations());
            $isAdminPreview = auth('admin')->check() || (auth()->user()?->isAdmin() ?? false);

            if (! $isAdminPreview) {
                $query->published();
            }

            if ($product = $query->where('slug', $slug)->first()) {
                return $this->fromModel($product);
            }
        }

        return collect($this->all())->firstWhere('slug', $slug);
    }

    /**
     * Load a card-sized product payload without customization, fulfillment,
     * FAQ, roster, and full description relation graphs.
     */
    public function findLightweightBySlug(string $slug): ?array
    {
        if (Schema::hasTable('products')) {
            $query = Product::query()->with($this->listingRelations());
            $isAdminPreview = auth('admin')->check() || (auth()->user()?->isAdmin() ?? false);

            if (! $isAdminPreview) {
                $query->published();
            }

            if ($product = $query->where('slug', $slug)->first()) {
                return $this->fromListingModel($product);
            }
        }

        return collect($this->all())->firstWhere('slug', $slug);
    }

    /**
     * Backwards-compatible alias. New callers should deliberately choose
     * findFullBySlug() or findLightweightBySlug().
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findFullBySlug($slug);
    }

    public function relatedFor(array $product, int $limit = 5): array
    {
        $limit = max(1, min($limit, 12));

        if (Schema::hasTable('products') && ! empty($product['id'])) {
            $categoryIds = collect($product['categories'] ?? [])
                ->pluck('id')
                ->push($product['category_id'] ?? null)
                ->push($product['subcategory_id'] ?? null)
                ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $candidates = Product::query()
                ->published()
                ->where('products.id', '!=', (int) $product['id'])
                ->with($this->listingRelations())
                ->when($categoryIds->isNotEmpty(), function (Builder $query) use ($categoryIds): void {
                    $query->where(function (Builder $categoryQuery) use ($categoryIds): void {
                        $categoryQuery
                            ->whereIn('category_id', $categoryIds)
                            ->orWhereIn('subcategory_id', $categoryIds)
                            ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $categoryIds));
                    });
                })
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->limit(max(20, $limit * 6))
                ->get()
                ->map(fn (Product $candidate): array => $this->fromListingModel($candidate));

            if ($candidates->isNotEmpty()) {
                return $this->rankRelatedProducts($candidates, $product, $limit);
            }
        }

        return $this->rankRelatedProducts(
            collect($this->all())->reject(fn (array $item): bool => ($item['slug'] ?? null) === ($product['slug'] ?? null)),
            $product,
            $limit
        );
    }


    /** @return array<int, string> */
    private function fullProductRelations(): array
    {
        return [
            'category',
            'subcategory',
            'categories',
            'attributeValues.attribute',
            'images',
            'optionGroups.values',
            'sizeGroups.sizes',
            'priceTiers',
            'fabricPriceTables.tiers',
            'artworkMethods',
            'productionSpeeds.productionMethod',
            'shippingMethods',
            'faqs',
        ];
    }

    /** @return array<int, string> */
    private function suggestionRelations(): array
    {
        return [
            'category',
            'subcategory',
            'categories',
            'images',
            'priceTiers',
        ];
    }

    /** @return array<string, mixed> */
    private function fromSuggestionModel(Product $product): array
    {
        $primaryCategory = $product->category
            ?: $product->subcategory
            ?: ($product->relationLoaded('categories')
                ? ($product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first())
                : null);
        $primaryImage = $product->images->first();
        $unitPrice = $this->displayUnitPrice($product);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'title' => $product->name,
            'short_title' => $product->name,
            'summary' => $product->short_description ?: str(strip_tags((string) $product->description_html))->limit(130)->toString(),
            'sku' => $product->sku,
            'category' => $primaryCategory?->name ?: 'Custom Sportswear',
            'sport' => $primaryCategory?->name ?: 'Custom Sportswear',
            'price' => $this->formatDisplayPrice($unitPrice),
            'base_price' => (float) $product->base_price,
            'currency' => $product->currency,
            'image' => $primaryImage?->publicUrl() ?: asset('images/product-placeholder.svg'),
            'alt' => $primaryImage?->alt_text ?: $product->name,
            'url' => route('products.show', $product->slug),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function rankRelatedProducts(\Illuminate\Support\Collection $products, array $product, int $limit): array
    {
        $productTags = collect($product['tags'] ?? [])
            ->map(fn ($tag): string => Str::lower(trim((string) $tag)))
            ->filter()
            ->all();

        return $products
            ->sortByDesc(function (array $item) use ($product, $productTags): int {
                $score = 0;

                if (($item['sport'] ?? null) === ($product['sport'] ?? null)) {
                    $score += 3;
                }

                if (($item['category'] ?? null) === ($product['category'] ?? null)) {
                    $score += 2;
                }

                $itemTags = collect($item['tags'] ?? [])
                    ->map(fn ($tag): string => Str::lower(trim((string) $tag)))
                    ->filter()
                    ->all();

                if (array_intersect($itemTags, $productTags) !== []) {
                    $score += 1;
                }

                return $score;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function featured(?int $limit = 8): array
    {
        $normalizedLimit = $limit === null ? null : max(1, min($limit, 24));
        $cacheIndex = $normalizedLimit === null ? 'all' : (string) $normalizedLimit;

        if (array_key_exists($cacheIndex, $this->featuredProducts)) {
            return $this->featuredProducts[$cacheIndex];
        }

        if (Schema::hasTable('products')) {
            $cacheVersion = $this->catalogCacheVersionSuffix();
            $cacheKey = 'catalog.homepage-featured.'.$cacheVersion.'.'.$cacheIndex;
            $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

            $databaseProducts = Cache::remember(
                $cacheKey,
                $ttl,
                function () use ($normalizedLimit): array {
                    $featuredQuery = Product::query()
                        ->published()
                        ->featured()
                        ->with($this->listingRelations())
                        ->orderBy('sort_order')
                        ->orderByDesc('published_at')
                        ->orderByDesc('updated_at');

                    if ($normalizedLimit !== null) {
                        $featuredQuery->limit($normalizedLimit);
                    }

                    $products = $featuredQuery->get();

                    // Limited callers keep the historical fallback behaviour.
                    // The homepage requests all featured products and therefore
                    // only receives products explicitly marked as featured.
                    if ($normalizedLimit !== null && $products->count() < $normalizedLimit) {
                        $fallback = Product::query()
                            ->published()
                            ->whereNotIn('id', $products->modelKeys())
                            ->with($this->listingRelations())
                            ->orderBy('sort_order')
                            ->orderByDesc('published_at')
                            ->limit($normalizedLimit - $products->count())
                            ->get();

                        $products = $products->concat($fallback);
                    }

                    return $products
                        ->map(fn (Product $product): array => $this->fromListingModel($product))
                        ->values()
                        ->all();
                }
            );

            if ($databaseProducts !== []) {
                return $this->featuredProducts[$cacheIndex] = $databaseProducts;
            }
        }

        $fallback = collect($this->products())
            ->map(fn (array $product): array => $this->hydrateProduct($product));
        $featured = $fallback->where('is_featured', true)->values();

        if ($normalizedLimit === null) {
            return $this->featuredProducts[$cacheIndex] = $featured->all();
        }

        $featured = $featured->take($normalizedLimit)->values();

        if ($featured->count() < $normalizedLimit) {
            $featured = $featured->concat(
                $fallback
                    ->reject(fn (array $product): bool => $featured->contains('slug', $product['slug']))
                    ->take($normalizedLimit - $featured->count())
            );
        }

        return $this->featuredProducts[$cacheIndex] = $featured->values()->all();
    }

    public function latest(int $limit = 4): array
    {
        $limit = max(1, min($limit, 24));

        if (array_key_exists($limit, $this->latestProducts)) {
            return $this->latestProducts[$limit];
        }

        if (Schema::hasTable('products')) {
            $cacheVersion = $this->catalogCacheVersionSuffix();
            $cacheKey = 'catalog.homepage-latest.'.$cacheVersion.'.'.$limit;
            $ttl = max(60, (int) config('catalog.category_cache_seconds', 1800));

            $databaseProducts = Cache::remember(
                $cacheKey,
                $ttl,
                fn (): array => Product::query()
                    ->published()
                    ->with($this->listingRelations())
                    ->orderByDesc('updated_at')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Product $product): array => $this->fromListingModel($product))
                    ->values()
                    ->all()
            );

            if ($databaseProducts !== []) {
                return $this->latestProducts[$limit] = $databaseProducts;
            }
        }

        return $this->latestProducts[$limit] = collect($this->products())
            ->reverse()
            ->take($limit)
            ->map(fn (array $product): array => $this->hydrateProduct($product))
            ->values()
            ->all();
    }

    public function bestSelling(int $limit = 4): array
    {
        $limit = max(1, min($limit, 24));

        if (array_key_exists($limit, $this->bestSellingProducts)) {
            return $this->bestSellingProducts[$limit];
        }

        if (Schema::hasTable('products')) {
            $cacheVersion = $this->catalogCacheVersionSuffix();
            $cacheKey = 'catalog.homepage-best-selling.'.$cacheVersion.'.'.$limit;
            $ttl = max(60, min(600, (int) config('catalog.category_cache_seconds', 1800)));

            $databaseProducts = Cache::remember(
                $cacheKey,
                $ttl,
                fn (): array => $this->bestSellingFromDatabase($limit)
            );

            if ($databaseProducts !== []) {
                return $this->bestSellingProducts[$limit] = $databaseProducts;
            }
        }

        $fallback = collect($this->all())
            ->sortByDesc(fn (array $product): int => (int) ($product['is_featured'] ?? false))
            ->take($limit)
            ->values()
            ->all();

        return $this->bestSellingProducts[$limit] = $fallback;
    }

    private function catalogCacheVersionSuffix(): string
    {
        $categoryVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $productVersion = (int) Cache::get(ProductCatalogCacheService::PRODUCT_VERSION_KEY, 1);

        return $categoryVersion.'.'.$productVersion;
    }

    /** @return array<int, array<string, mixed>> */
    private function bestSellingFromDatabase(int $limit): array
    {
        $rankedIds = collect();

        if (Schema::hasTable('orders') && Schema::hasTable('order_items')) {
            $netQuantity = '(order_items.quantity - order_items.cancelled_quantity - order_items.returned_quantity)';

            $rankedIds = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNotNull('order_items.product_id')
                ->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['paid', 'partially_refunded'])
                ->where('orders.status', '<>', 'cancelled')
                ->select('order_items.product_id')
                ->selectRaw("SUM(CASE WHEN {$netQuantity} > 0 THEN {$netQuantity} ELSE 0 END) AS sold_quantity")
                ->groupBy('order_items.product_id')
                ->havingRaw("SUM(CASE WHEN {$netQuantity} > 0 THEN {$netQuantity} ELSE 0 END) > 0")
                ->orderByDesc('sold_quantity')
                ->orderByDesc('order_items.product_id')
                ->limit($limit)
                ->pluck('order_items.product_id')
                ->map(fn ($id): int => (int) $id)
                ->values();
        }

        $productsById = $rankedIds->isEmpty()
            ? collect()
            : Product::query()
                ->published()
                ->whereIn('id', $rankedIds->all())
                ->with($this->listingRelations())
                ->get()
                ->keyBy('id');

        $products = $rankedIds
            ->map(fn (int $id) => $productsById->get($id))
            ->filter()
            ->map(fn (Product $product): array => $this->fromListingModel($product))
            ->values();

        if ($products->count() < $limit) {
            $excludedIds = $products->pluck('id')->all();
            $fallback = Product::query()
                ->published()
                ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
                ->with($this->listingRelations())
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit - $products->count())
                ->get()
                ->map(fn (Product $product): array => $this->fromListingModel($product));

            $products = $products->concat($fallback);
        }

        return $products->take($limit)->values()->all();
    }

    private function normalizeColorHex(?string $color): ?string
    {
        $hex = strtoupper(ltrim(trim((string) $color), '#'));

        if (preg_match('/^[0-9A-F]{3}$/', $hex) === 1) {
            $hex = implode('', array_map(
                static fn (string $character): string => $character.$character,
                str_split($hex)
            ));
        }

        return preg_match('/^[0-9A-F]{6}$/', $hex) === 1 ? '#'.$hex : null;
    }

    private function contrastColor(?string $color): string
    {
        $hex = $this->normalizeColorHex($color);

        if ($hex === null) {
            return '#0F172A';
        }

        $red = hexdec(substr($hex, 1, 2));
        $green = hexdec(substr($hex, 3, 2));
        $blue = hexdec(substr($hex, 5, 2));
        $luminance = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $luminance > 160 ? '#0F172A' : '#FFFFFF';
    }

    public function fromListingModel(Product $product): array
    {
        $gallery = $product->images->map(fn ($image) => [
            'url' => $image->publicUrl(),
            'alt' => $image->alt_text ?: $product->name,
        ])->values()->all();

        if ($gallery === []) {
            $gallery[] = ['url' => asset('images/product-placeholder.svg'), 'alt' => $product->name];
        }

        $primaryCategory = $product->category ?: $product->subcategory;
        $primaryCategory ??= $product->relationLoaded('categories')
            ? ($product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first())
            : null;

        $visibleCategories = collect([$primaryCategory, $product->subcategory])
            ->filter()
            ->unique(fn ($category) => $category->id)
            ->values();

        $unitPrice = $this->displayUnitPrice($product);

        $detailInformation = $this->normalizeDetailInformation($product->specifications ?? []);
        $summaryDetailInformation = $this->summaryDetailInformation($detailInformation, $product);
        $specificationSku = trim((string) (($summaryDetailInformation['SKU'] ?? null) ?: ($detailInformation['SKU'] ?? '')));
        $rating = $this->genuineProductRating($product);
        $reviewsCount = $this->genuineProductReviewsCount($product);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'title' => $product->name,
            'short_title' => $product->name,
            'summary' => $product->short_description ?: str(strip_tags((string) $product->description_html))->limit(130)->toString(),
            'description' => strip_tags((string) $product->description_html),
            'price' => $this->formatDisplayPrice($unitPrice),
            'base_price' => (float) $product->base_price,
            'currency' => $product->currency,
            'minimum_quantity' => $product->minimum_quantity,
            'maximum_quantity' => $product->maximum_quantity,
            'tag' => $product->badge_label ?: ($product->is_customizable ? 'Customizable' : null),
            'tag_color' => $product->badge_color ?: 'red',
            'sport' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category_slug' => $primaryCategory?->slug,
            'subcategory' => $product->subcategory?->name,
            'subcategory_slug' => $product->subcategory?->slug,
            'categories' => $visibleCategories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'primary' => (int) $category->id === (int) ($primaryCategory?->id),
            ])->values()->all(),
            'attributes' => [],
            'sku' => $specificationSku !== '' ? $specificationSku : $product->sku,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'has_reviews' => $rating !== null && $reviewsCount !== null && $reviewsCount > 0,
            'customization_options' => $this->productCardCustomizationOptions($product),
            'has_bulk_pricing' => $this->hasBulkPricing($product),
            'shopper_activity' => $this->productShopperActivity($product),
            'image' => $gallery[0]['url'],
            'alt' => $gallery[0]['alt'],
            'gallery' => $gallery,
            'tags' => $product->tags ?? [],
            'features' => $product->features ?? [],
            'summary_detail_information' => $summaryDetailInformation,
            'brand' => $product->brand ?: config('storefront.name'),
            'product_type' => $product->product_type,
            'is_featured' => $product->is_featured,
            'is_customizable' => $product->is_customizable,
            'track_inventory' => $product->track_inventory,
            'stock_quantity' => $product->stock_quantity,
            'allow_backorder' => $product->allow_backorder,
            'url' => route('products.show', $product->slug),
        ];
    }

    /** @return array<int, string> */
    public function listingRelations(): array
    {
        return [
            'category',
            'subcategory',
            'categories',
            'images',
            'priceTiers',
            'optionGroups' => fn ($query) => $query
                ->where('is_active', true)
                ->where(function ($builder): void {
                    $builder->whereNull('display_mode')->orWhere('display_mode', '!=', 'hidden');
                })
                ->where(function ($builder): void {
                    $builder->whereNull('show_in_summary')->orWhere('show_in_summary', true);
                })
                ->orderBy('sort_order'),
        ];
    }

    private function displayUnitPrice(Product $product, ?array $priceTiers = null): float
    {
        if ($priceTiers !== null) {
            $lastTier = collect($priceTiers)
                ->filter(fn (array $tier): bool => is_numeric($tier['unit'] ?? null))
                ->sortBy(fn (array $tier): int => (int) ($tier['min'] ?? 0))
                ->last();

            if (is_array($lastTier)) {
                return (float) $lastTier['unit'];
            }
        } elseif ($product->relationLoaded('priceTiers')) {
            $lastTier = $product->priceTiers
                ->filter(fn ($tier): bool => is_numeric($tier->unit_price))
                ->sortBy('minimum_quantity')
                ->last();

            if ($lastTier) {
                return (float) $lastTier->unit_price;
            }
        }

        $priceFromTable = $this->priceFromLastVisiblePriceRow($product);

        return $priceFromTable ?? (float) $product->base_price;
    }

    private function priceFromLastVisiblePriceRow(Product $product): ?float
    {
        $rows = collect($product->price_table_rows ?? [])
            ->filter(fn ($row): bool => is_array($row) && count($row) > 1)
            ->values();

        if ($rows->isEmpty()) {
            return null;
        }

        $lastRow = array_values($rows->last());
        $preferredColumn = (int) ($product->price_table_highlight_column ?? 1);
        $price = $this->parseDisplayMoney($lastRow[$preferredColumn] ?? null);

        if ($price !== null) {
            return $price;
        }

        foreach (array_slice($lastRow, 1) as $cell) {
            $price = $this->parseDisplayMoney($cell);

            if ($price !== null) {
                return $price;
            }
        }

        return null;
    }

    private function parseDisplayMoney(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:,\d{3})*(?:\.\d+)?|-?\d+(?:\.\d+)?/', $value, $match) !== 1) {
            return null;
        }

        return (float) str_replace(',', '', $match[0]);
    }

    private function formatDisplayPrice(float $unitPrice): string
    {
        return 'From $'.number_format($unitPrice, 2);
    }

    private function genuineProductRating(Product $product): ?float
    {
        foreach (['rating_average', 'average_rating', 'rating'] as $column) {
            if (! array_key_exists($column, $product->getAttributes())) {
                continue;
            }

            $rating = $product->getAttribute($column);

            if (is_numeric($rating) && (float) $rating > 0) {
                return round(min(5, max(0, (float) $rating)), 1);
            }
        }

        $schemaRating = data_get($product->schema_json, 'aggregateRating.ratingValue')
            ?? data_get($product->schema_json, 'aggregateRating.rating')
            ?? data_get($product->schema_json, 'ratingValue');

        if (is_numeric($schemaRating) && (float) $schemaRating > 0) {
            return round(min(5, max(0, (float) $schemaRating)), 1);
        }

        $specRating = $this->numericSpecificationValue($product, ['rating', 'average rating', 'rating average']);
        if ($specRating !== null && $specRating > 0) {
            return round(min(5, max(0, $specRating)), 1);
        }

        return null;
    }

    private function genuineProductReviewsCount(Product $product): ?int
    {
        foreach (['reviews_count', 'review_count', 'published_reviews_count'] as $column) {
            if (! array_key_exists($column, $product->getAttributes())) {
                continue;
            }

            $count = $product->getAttribute($column);

            if (is_numeric($count) && (int) $count > 0) {
                return (int) $count;
            }
        }

        $schemaCount = data_get($product->schema_json, 'aggregateRating.reviewCount')
            ?? data_get($product->schema_json, 'aggregateRating.ratingCount')
            ?? data_get($product->schema_json, 'reviewCount');

        if (is_numeric($schemaCount) && (int) $schemaCount > 0) {
            return (int) $schemaCount;
        }

        $specCount = $this->numericSpecificationValue($product, ['reviews count', 'review count', 'reviews', 'rating count']);
        if ($specCount !== null && (int) $specCount > 0) {
            return (int) $specCount;
        }

        return null;
    }

    /**
     * Return only written reviews that are already present in the product's
     * JSON-LD/schema payload. This deliberately avoids generating placeholder
     * testimonials or attributing global testimonials to a specific product.
     *
     * Supported schema shapes include a Product object, an @graph array, a
     * top-level array of schema nodes, and either `review` or `reviews` keys.
     *
     * @return array<int, array<string, mixed>>
     */
    private function genuineProductReviewItems(Product $product): array
    {
        $schema = $product->schema_json;

        if (! is_array($schema) || $schema === []) {
            return [];
        }

        $nodes = collect();

        if (array_is_list($schema)) {
            $nodes = $nodes->concat($schema);
        } else {
            $nodes->push($schema);
        }

        foreach ((array) data_get($schema, '@graph', []) as $graphNode) {
            if (is_array($graphNode)) {
                $nodes->push($graphNode);
            }
        }

        $reviews = $nodes
            ->flatMap(function ($node): array {
                if (! is_array($node)) {
                    return [];
                }

                $payload = $node['review'] ?? $node['reviews'] ?? [];

                if (! is_array($payload) || $payload === []) {
                    return [];
                }

                return array_is_list($payload) ? $payload : [$payload];
            })
            ->filter(fn ($review): bool => is_array($review))
            ->map(function (array $review): ?array {
                $body = trim(strip_tags((string) ($review['reviewBody'] ?? $review['description'] ?? '')));
                $authorPayload = $review['author'] ?? null;
                $author = trim((string) (is_array($authorPayload)
                    ? ($authorPayload['name'] ?? '')
                    : $authorPayload));
                $authorDetail = trim((string) (is_array($authorPayload)
                    ? ($authorPayload['jobTitle'] ?? $authorPayload['description'] ?? '')
                    : ''));
                $rating = data_get($review, 'reviewRating.ratingValue')
                    ?? data_get($review, 'reviewRating.rating')
                    ?? ($review['rating'] ?? null);

                if ($body === '' || ! is_numeric($rating) || (float) $rating <= 0) {
                    return null;
                }

                $rating = round(min(5, max(1, (float) $rating)), 1);
                $verifiedValue = $review['verified']
                    ?? $review['isVerified']
                    ?? data_get($review, 'author.verified')
                    ?? false;

                return [
                    'title' => trim(strip_tags((string) ($review['name'] ?? $review['headline'] ?? ''))),
                    'body' => $body,
                    'rating' => $rating,
                    'author' => $author !== '' ? $author : 'Customer',
                    'author_detail' => $authorDetail,
                    'verified' => filter_var($verifiedValue, FILTER_VALIDATE_BOOLEAN),
                    'date' => trim((string) ($review['datePublished'] ?? '')),
                ];
            })
            ->filter()
            ->unique(fn (array $review): string => Str::lower($review['author'].'|'.$review['body']))
            ->take(12)
            ->values();

        return $reviews->all();
    }

    /**
     * @param array<int, array<string, mixed>> $reviewItems
     * @return array<int, array{stars:int,count:int,percent:float}>
     */
    private function genuineReviewDistribution(array $reviewItems): array
    {
        $items = collect($reviewItems);
        $total = $items->count();

        if ($total === 0) {
            return [];
        }

        return collect(range(5, 1))
            ->map(function (int $stars) use ($items, $total): array {
                $count = $items->filter(
                    fn (array $review): bool => (int) round((float) ($review['rating'] ?? 0)) === $stars
                )->count();

                return [
                    'stars' => $stars,
                    'count' => $count,
                    'percent' => round(($count / $total) * 100, 2),
                ];
            })
            ->all();
    }

    /**
     * @param array<int, string> $labels
     */
    private function numericSpecificationValue(Product $product, array $labels): ?float
    {
        $specifications = collect($product->specifications ?? []);

        if ($specifications->isEmpty()) {
            return null;
        }

        $wanted = collect($labels)
            ->map(fn (string $label): string => Str::of($label)->lower()->squish()->toString())
            ->all();

        foreach ($specifications as $specification) {
            if (! is_array($specification)) {
                continue;
            }

            $name = Str::of((string) ($specification['name'] ?? $specification['label'] ?? $specification['key'] ?? ''))
                ->lower()
                ->squish()
                ->toString();

            if (! in_array($name, $wanted, true)) {
                continue;
            }

            $value = (string) ($specification['value'] ?? $specification['content'] ?? '');

            if (preg_match('/\d+(?:\.\d+)?/', $value, $match) === 1) {
                return (float) $match[0];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function productCardCustomizationOptions(Product $product): array
    {
        $labels = collect();

        if ((bool) $product->is_customizable) {
            $labels->push('Custom design');
        }

        if ((bool) ($product->artwork_upload_enabled ?? false)) {
            $labels->push('Artwork upload');
        }

        if ($product->relationLoaded('optionGroups')) {
            $product->optionGroups
                ->where('is_active', true)
                ->filter(fn ($group): bool => ($group->display_mode ?: 'customer') !== 'hidden' && (bool) ($group->show_in_summary ?? true))
                ->pluck('name')
                ->each(fn ($name) => $labels->push((string) $name));
        }

        collect($product->features ?? [])
            ->filter(fn ($feature): bool => is_scalar($feature))
            ->each(fn ($feature) => $labels->push((string) $feature));

        return $labels
            ->map(fn (string $label): string => trim(preg_replace('/\s+/', ' ', strip_tags($label)) ?? ''))
            ->filter(fn (string $label): bool => $label !== '')
            ->reject(function (string $label): bool {
                $normalized = Str::of($label)->lower()->replace(['&', '+'], ' and ')->squish()->toString();

                return str_contains($normalized, 'name and number')
                    || str_contains($normalized, 'names and numbers')
                    || str_contains($normalized, 'team pricing')
                    || str_contains($normalized, 'bulk pricing')
                    || str_contains($normalized, 'bulk order')
                    || str_contains($normalized, 'bulk quote');
            })
            ->unique(fn (string $label): string => Str::lower($label))
            ->take(3)
            ->values()
            ->all();
    }

    private function hasBulkPricing(Product $product): bool
    {
        if ($product->relationLoaded('priceTiers') && $product->priceTiers->where('unit_price', '>', 0)->count() > 1) {
            return true;
        }

        if (collect($product->price_table_rows ?? [])->filter(fn ($row): bool => is_array($row) && count($row) > 1)->count() > 1) {
            return true;
        }

        return (int) ($product->minimum_quantity ?? 1) > 1;
    }

    private function productShopperActivity(Product $product): ?string
    {
        // Product-card visitor activity is populated asynchronously from genuine
        // product-detail visits recorded in product_view_sessions. Do not render
        // manually entered or inferred counts as shopper activity.
        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function positiveIntegerAttribute(Product $product, array $columns): ?int
    {
        foreach ($columns as $column) {
            if (! array_key_exists($column, $product->getAttributes())) {
                continue;
            }

            $value = $product->getAttribute($column);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    public function fromModel(Product $product): array
    {
        $gallery = $product->images->map(fn ($image) => [
            'url' => $image->publicUrl(),
            'alt' => $image->alt_text ?: $product->name,
        ])->values()->all();

        if ($gallery === []) {
            $gallery[] = ['url' => asset('images/product-placeholder.svg'), 'alt' => $product->name];
        }

        $fabricPriceTables = $this->fabricPriceTablesForProduct($product);

        $optionGroups = $product->optionGroups
            ->where('is_active', true)
            ->filter(fn ($group) => ($group->display_mode ?: 'customer') !== 'hidden')
            ->map(fn ($group) => [
                'id' => $group->code,
                'label' => $group->name,
                'description' => $group->description,
                'placeholder' => $group->placeholder,
                'section' => $group->section,
                'type' => $group->type,
                'display_mode' => $group->display_mode ?: 'customer',
                'fixed_value_code' => $group->fixed_value_code,
                'fixed_text_value' => $group->fixed_text_value,
                'show_in_summary' => $group->show_in_summary,
                'required' => $group->is_required,
                'minimum_selections' => $group->minimum_selections,
                'maximum_selections' => $group->maximum_selections,
                'accepted_file_types' => $group->accepted_file_types,
                'maximum_file_size_mb' => $group->maximum_file_size_mb,
                'values' => $group->values->where('is_active', true)->map(fn ($value) => [
                    'id' => $value->code,
                    'label' => $value->label,
                    'description' => $value->description,
                    'color' => $this->normalizeColorHex($value->color_hex),
                    'contrast' => $this->contrastColor($value->color_hex),
                    'image' => $value->publicImageUrl(),
                    'images' => $value->publicImages(),
                    'price_delta' => (float) $value->price_adjustment,
                    'charge_type' => $value->charge_type ?: 'per_unit',
                    'stock_quantity' => $value->stock_quantity,
                    'default' => $value->is_default,
                    'fabric_price_table' => $this->fabricPriceTableForOptionValue($value, $fabricPriceTables),
                ])->values()->all(),
            ])->values()->all();

        $priceTiers = $product->priceTiers->map(fn ($tier) => [
            'label' => $tier->label ?: $tier->minimum_quantity . ($tier->maximum_quantity ? '–'.$tier->maximum_quantity : '+'),
            'min' => $tier->minimum_quantity,
            'max' => $tier->maximum_quantity,
            'unit' => (float) $tier->unit_price,
            'compare_at' => $tier->compare_at_price ? (float) $tier->compare_at_price : null,
            'savings_label' => $tier->savings_label,
        ])->values()->all();

        if ($priceTiers === []) {
            $priceTiers[] = ['label' => $product->minimum_quantity.'+', 'min' => $product->minimum_quantity, 'max' => null, 'unit' => (float) $product->base_price, 'compare_at' => null, 'savings_label' => null];
        }

        $visiblePriceRows = collect($product->price_table_rows ?: collect($priceTiers)->map(fn ($tier) => [
            (string) ($tier['label'] ?? $tier['min']),
            '$'.number_format($tier['unit'], 2),
            $tier['savings_label'] ?: '—',
        ])->all())->map(function ($row, int $index) use ($priceTiers): array {
            $row = array_values((array) $row);
            if (isset($priceTiers[$index]['min'])) {
                $row[0] = (string) ($priceTiers[$index]['label'] ?? $priceTiers[$index]['min']);
            }

            return $row;
        })->values()->all();

        $priceTableHeaders = $product->price_table_headers ?: ['Quantity', 'Unit Price', 'Savings'];
        $priceTablePayload = [
            'headers' => $priceTableHeaders,
            'rows' => $visiblePriceRows,
            'highlight_column' => $product->price_table_highlight_column,
            'note' => $product->price_table_note,
            'price_tiers' => $priceTiers,
        ];

        $primaryCategory = $product->category ?: $product->subcategory;
        $primaryCategory ??= $product->relationLoaded('categories')
            ? ($product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first())
            : null;

        $visibleCategories = collect([$primaryCategory, $product->subcategory])
            ->filter()
            ->unique(fn ($category) => $category->id)
            ->values();

        $detailInformation = $this->normalizeDetailInformation($product->specifications ?? []);
        $summaryDetailInformation = $this->summaryDetailInformation($detailInformation, $product);
        $specificationSku = trim((string) (($summaryDetailInformation['SKU'] ?? null) ?: ($detailInformation['SKU'] ?? '')));
        $productProfile = $product->product_profile ?: 'standard';
        $supportsSizeOptions = ProductSizing::supports($productProfile);
        $rating = $this->genuineProductRating($product);
        $reviewsCount = $this->genuineProductReviewsCount($product);
        $reviewItems = $this->genuineProductReviewItems($product);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'title' => $product->name,
            'short_title' => $product->name,
            'summary' => $product->short_description ?: strip_tags((string) $product->description_html),
            'description' => strip_tags((string) $product->description_html),
            'description_html' => $product->description_html,
            'detail_information_html' => $product->detail_information_html,
            'customization_artwork_html' => $product->customization_artwork_html,
            'fulfillment_html' => $product->fulfillment_html,
            'price' => $this->formatDisplayPrice($this->displayUnitPrice($product, $priceTiers)),
            'base_price' => (float) $product->base_price,
            'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
            'currency' => $product->currency,
            'minimum_quantity' => $product->minimum_quantity,
            'maximum_quantity' => $product->maximum_quantity,
            'tag' => $product->badge_label ?: ($product->is_customizable ? 'Customizable' : null),
            'tag_color' => $product->badge_color ?: 'red',
            'sport' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category_slug' => $primaryCategory?->slug,
            'subcategory' => $product->subcategory?->name,
            'subcategory_slug' => $product->subcategory?->slug,
            'categories' => $visibleCategories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug, 'primary' => (int) $category->id === (int) ($primaryCategory?->id)])->values()->all(),
            'attributes' => $product->relationLoaded('attributeValues') ? $product->attributeValues->groupBy('attribute.slug')->map(fn ($values) => $values->pluck('label')->values()->all())->all() : [],
            'sku' => $specificationSku !== '' ? $specificationSku : $product->sku,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'has_reviews' => $rating !== null && $reviewsCount !== null && $reviewsCount > 0,
            'review_items' => $reviewItems,
            'review_distribution' => $this->genuineReviewDistribution($reviewItems),
            'favorites_count' => is_numeric($product->favorites_count) && (int) $product->favorites_count > 0
                ? (int) $product->favorites_count
                : null,
            'customization_options' => $this->productCardCustomizationOptions($product),
            'has_bulk_pricing' => $this->hasBulkPricing($product),
            'shopper_activity' => $this->productShopperActivity($product),
            'image' => $gallery[0]['url'],
            'alt' => $gallery[0]['alt'],
            'gallery' => $gallery,
            'tags' => $product->tags ?? [],
            'features' => $product->features ?? [],
            'summary_detail_information' => $summaryDetailInformation,
            'detail_information' => $detailInformation,
            'details' => $detailInformation,
            'brand' => $product->brand ?: config('storefront.name'),
            'product_type' => $product->product_type,
            'product_profile' => $productProfile,
            'jersey_roster' => [
                'enabled' => (bool) $product->jersey_roster_enabled && ProductRoster::supports($productProfile),
                'optional' => (bool) $product->jersey_roster_optional,
                'title' => $product->jersey_roster_title ?: 'Add player names and numbers',
                'fields' => collect($product->jersey_roster_fields ?? [])->filter(fn ($field) => (bool) ($field['enabled'] ?? true))->values()->all(),
            ],
            'is_featured' => $product->is_featured,
            'is_customizable' => $product->is_customizable,
            'track_inventory' => $product->track_inventory,
            'stock_quantity' => $product->stock_quantity,
            'allow_backorder' => $product->allow_backorder,
            'option_groups' => $optionGroups,
            'size_groups' => $supportsSizeOptions ? $product->sizeGroups->where('is_active', true)->map(fn ($group) => [
                'id' => $group->code,
                'label' => $group->name,
                'description_html' => $group->description_html,
                'sizes' => $group->sizes->where('is_active', true)->map(fn ($size) => [
                    'code' => $size->code,
                    'label' => $size->label,
                    // Sizes select quantity only. Total quantity chooses the price tier.
                    'price_delta' => 0.0,
                ])->values()->all(),
                'chart' => [
                    'enabled' => (bool) $group->chart_enabled && (filled($group->chart_html) || filled($group->chartImageUrl()) || (! empty($group->chart_columns) && ! empty($group->chart_rows))),
                    'html' => $group->chart_html,
                    'title' => $group->chart_title ?: $group->name.' Size Chart',
                    'note' => $group->chart_note,
                    'columns' => $group->chart_columns ?? [],
                    'rows' => $group->chart_rows ?? [],
                    'image' => $group->chartImageUrl(),
                ],
            ])->values()->all() : [],
            'artwork_upload' => [
                'enabled' => (bool) $product->artwork_upload_enabled,
                'required' => (bool) $product->artwork_upload_required,
                'title' => $product->artwork_upload_title ?: 'Upload Custom Artwork',
                'description' => $product->artwork_upload_description ?: 'Upload one or more artwork files for the production team.',
                'max_files' => max(1, min(12, (int) ($product->artwork_upload_max_files ?: 5))),
                'max_file_size_mb' => max(1, min(25, (int) ($product->artwork_upload_max_file_size_mb ?: 15))),
                'accepted_types' => collect(explode(',', (string) ($product->artwork_upload_accepted_types ?: 'pdf,svg,png,jpg,jpeg,webp')))
                    ->map(fn ($type) => Str::lower(ltrim(trim((string) $type), '.')))
                    ->filter()->unique()->values()->all(),
            ],
            'artwork_methods' => [],
            'production_methods_enabled' => (bool) ($product->production_methods_enabled ?? false),
            'production_speeds' => ($product->production_methods_enabled ?? false) ? $product->productionSpeeds->where('is_active', true)->map(function ($speed) {
                $method = $speed->relationLoaded('productionMethod') ? $speed->productionMethod : null;
                $name = $method?->name ?: $speed->name;
                $description = $method?->description ?: $speed->description;
                $minimumDays = (int) ($method?->minimum_days ?? $speed->minimum_days);
                $maximumDays = (int) ($method?->maximum_days ?? $speed->maximum_days);

                return [
                    'id' => $speed->code,
                    'label' => $name,
                    'description' => $description,
                    'price_delta' => (float) $speed->price_adjustment,
                    'minimum_quantity' => (int) ($speed->minimum_quantity ?: 1),
                    'maximum_quantity' => $speed->maximum_quantity === null ? null : (int) $speed->maximum_quantity,
                    'minimum_days' => $minimumDays,
                    'maximum_days' => $maximumDays,
                    'production_time' => ($minimumDays === 0 && $maximumDays === 0)
                        ? 'To be confirmed'
                        : null,
                ];
            })->values()->all() : [],
            'shipping_methods_enabled' => (bool) $product->shipping_methods_enabled,
            'shipping_methods' => $product->shipping_methods_enabled ? $product->shippingMethods->where('is_active', true)->map(function ($method) use ($priceTableHeaders): array {
                $chargeType = $method->charge_type ?: 'per_unit';
                $isMasterMethod = $chargeType === 'master_method';
                $priceTableColumn = PriceTableShipping::columnIndex((array) $priceTableHeaders, (string) $method->name, (string) $method->code);

                // Master shipping records only provide the customer-facing name and
                // day range. Their price must come from the matching product/fabric
                // price-table column. Older saved products may still have legacy
                // price_adjustment values, so force master-linked methods to use the
                // price table and never expose those stale legacy charges.
                $usesPriceTable = (bool) $method->shipping_method_id || $chargeType === 'price_table' || $priceTableColumn !== null;

                return [
                    'id' => $method->code,
                    'label' => $method->name,
                    'description' => $method->description,
                    'price_source' => $usesPriceTable ? 'price_table' : 'legacy',
                    'requires_price_table' => $usesPriceTable,
                    'price_table_column' => $priceTableColumn,
                    'price_table_header' => $priceTableColumn !== null ? ($priceTableHeaders[$priceTableColumn] ?? null) : null,
                    'price_delta' => $usesPriceTable ? 0.0 : ($isMasterMethod ? (float) ($method->base_price ?? $method->price_adjustment) : (float) $method->price_adjustment),
                    'base_price' => $usesPriceTable ? 0.0 : ($isMasterMethod ? (float) ($method->base_price ?? $method->price_adjustment) : 0.0),
                    'per_item_price' => $usesPriceTable ? 0.0 : ($isMasterMethod ? (float) ($method->per_item_price ?? 0) : 0.0),
                    'free_shipping_minimum' => $method->free_shipping_minimum !== null ? (float) $method->free_shipping_minimum : null,
                    'charge_type' => $usesPriceTable ? 'price_table' : ($isMasterMethod ? 'master_method' : $chargeType),
                    'charge_application' => $method->charge_application,
                    'minimum_days' => $method->minimum_days,
                    'maximum_days' => $method->maximum_days,
                    'default' => (bool) $method->is_default,
                    'is_quote_based' => (bool) ($method->is_quote_based ?? false),
                ];
            })->values()->all() : [],
            'price_tiers' => $priceTiers,
            'price_table' => $priceTablePayload,
            'fabric_price_tables' => collect($fabricPriceTables)->unique(fn ($table) => (string) ($table['key'] ?? $table['fabric_code'] ?? $table['label'] ?? ''))->values()->all(),
            'faqs' => $product->faqs->where('is_active', true)->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer])->values()->all(),
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'canonical_url' => $product->canonical_url,
            'og_title' => $product->og_title,
            'og_description' => $product->og_description,
            'og_image' => $product->og_image_url ?: $gallery[0]['url'],
            'robots' => ($product->robots_index ? 'index' : 'noindex').', '.($product->robots_follow ? 'follow' : 'nofollow'),
            'custom_schema' => $product->schema_json,
            'url' => route('products.show', $product->slug),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fabricPriceTablesForProduct(Product $product): array
    {
        if (! $product->relationLoaded('fabricPriceTables')) {
            return [];
        }

        return $product->fabricPriceTables
            ->where('is_active', true)
            ->mapWithKeys(function (ProductFabricPriceTable $table): array {
                $formatted = $this->formatFabricPriceTable($table);
                $keys = [];
                $fabricKey = trim((string) $table->fabric_key);

                if ($fabricKey !== '') {
                    $keys[$fabricKey] = $formatted;
                }

                if ($table->jersey_customization_option_id) {
                    $keys['master:'.$table->jersey_customization_option_id] = $formatted;
                }

                if (filled($table->fabric_code)) {
                    $keys['code:'.Str::slug((string) $table->fabric_code)] = $formatted;
                }

                if ($keys === []) {
                    $keys['fabric-table:'.$table->id] = $formatted;
                }

                return $keys;
            })
            ->all();
    }

    /**
     * @param array<string, array<string, mixed>> $fabricPriceTables
     */
    private function fabricPriceTableForOptionValue($value, array $fabricPriceTables): ?array
    {
        $keys = collect([
            $value->jersey_customization_option_id ? 'master:'.$value->jersey_customization_option_id : null,
            filled($value->code) ? 'code:'.Str::slug((string) $value->code) : null,
            filled($value->label) ? 'code:'.Str::slug((string) $value->label) : null,
        ])->filter()->values();

        foreach ($keys as $key) {
            if (isset($fabricPriceTables[$key])) {
                return $fabricPriceTables[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFabricPriceTable(ProductFabricPriceTable $table): array
    {
        $priceTiers = $table->tiers->map(fn ($tier) => [
            'label' => $tier->label ?: $tier->minimum_quantity . ($tier->maximum_quantity ? '–'.$tier->maximum_quantity : '+'),
            'min' => (int) $tier->minimum_quantity,
            'max' => $tier->maximum_quantity === null ? null : (int) $tier->maximum_quantity,
            'unit' => (float) $tier->unit_price,
            'compare_at' => $tier->compare_at_price ? (float) $tier->compare_at_price : null,
            'savings_label' => $tier->savings_label,
        ])->values()->all();

        $rows = collect($table->price_table_rows ?? [])->values();
        if ($rows->isEmpty()) {
            $rows = collect($priceTiers)->map(fn ($tier) => [
                (string) ($tier['label'] ?? $tier['min']),
                '$'.number_format((float) $tier['unit'], 2),
                $tier['savings_label'] ?: '—',
            ]);
        }

        $displayKey = trim((string) $table->fabric_key);
        if ($displayKey === '') {
            $displayKey = $table->jersey_customization_option_id
                ? 'master:'.$table->jersey_customization_option_id
                : (filled($table->fabric_code) ? 'code:'.Str::slug((string) $table->fabric_code) : 'fabric-table:'.$table->id);
        }

        return [
            'key' => $displayKey,
            'fabric_id' => $table->jersey_customization_option_id,
            'fabric_code' => $table->fabric_code,
            'label' => $table->fabric_label,
            'headers' => $table->price_table_headers ?: ['Quantity', 'Unit Price', 'Savings'],
            'rows' => $rows->values()->all(),
            'highlight_column' => (int) ($table->price_table_highlight_column ?: 1),
            'note' => $table->price_table_note,
            'price_tiers' => $priceTiers,
        ];
    }

    private function summaryDetailInformation(array $detailInformation, Product $product): array
    {
        $fabricDisplayLabel = $this->preferredFabricDetailLabel($detailInformation);
        $labels = ['SKU', 'Product Type', $fabricDisplayLabel, 'Fabric GSM', 'GSM', 'Width', 'Fit', 'Customization', 'Imprint Method', 'Attachment', 'Size Range', 'MOQ', 'Lead Time', 'Shipping Time', 'Usage', 'Standard Length'];
        $fallbacks = [
            'SKU' => $product->sku,
            'Product Type' => $product->product_type,
            'MOQ' => $product->minimum_quantity
                ? number_format((int) $product->minimum_quantity).' '.((int) $product->minimum_quantity === 1 ? 'Piece' : 'Pieces')
                : null,
        ];

        return collect($labels)
            ->mapWithKeys(function (string $label) use ($detailInformation, $fallbacks): array {
                $value = $this->detailValueForLabel($detailInformation, $label);

                if ($value === '' && isset($fallbacks[$label])) {
                    $value = trim((string) $fallbacks[$label]);
                }

                return $value !== '' ? [$label => $value] : [];
            })
            ->all();
    }

    private function normalizeDetailInformation(array $specifications): array
    {
        $rows = collect($specifications)
            ->mapWithKeys(function ($value, $label): array {
                if (is_array($value)) {
                    $rowLabel = $value['label'] ?? $value['name'] ?? null;
                    $rowValue = $value['value'] ?? $value['information'] ?? null;

                    return filled($rowLabel) && filled($rowValue)
                        ? [(string) $rowLabel => (string) $rowValue]
                        : [];
                }

                return filled($label) && filled($value)
                    ? [(string) $label => (string) $value]
                    : [];
            });

        $parsedRows = $this->parseDetailInformationText(
            $rows->map(fn ($value, $label) => $label.': '.$value)->implode(PHP_EOL)
        );

        return $this->orderedDetailInformationRows(collect($parsedRows ?: $rows->all())
            ->filter(fn ($value, $label) => filled($label) && filled($value))
            ->all());
    }

    private function orderedDetailInformationRows(array $rows): array
    {
        $fabricDisplayLabel = $this->preferredFabricDetailLabel($rows);
        $orderedLabels = ['SKU', 'Product Type', $fabricDisplayLabel, 'Fabric GSM', 'GSM', 'Width', 'Fit', 'Customization', 'Imprint Method', 'Attachment', 'Size Range', 'MOQ', 'Lead Time', 'Shipping Time', 'Usage', 'Standard Length'];
        $result = [];

        foreach ($orderedLabels as $label) {
            $value = $this->detailValueForLabel($rows, $label);
            if ($value !== '') {
                $result[$label] = $value;
            }
        }

        foreach ($rows as $label => $value) {
            $label = trim((string) $label);
            $value = trim((string) $value);

            if ($label === '' || $value === '') {
                continue;
            }

            if (isset($result[$label]) || in_array($label, $orderedLabels, true)) {
                continue;
            }

            if ($this->isFabricLikeDetailLabel($label)) {
                continue;
            }

            $result[$label] = $value;
        }

        return collect($result)->take(100)->all();
    }

    private function fabricLikeDetailLabels(): array
    {
        return ['Fabric', 'Material', 'Materials', 'Metarial', 'Metarials', 'Meterial', 'Meterials'];
    }

    private function isFabricLikeDetailLabel(?string $label): bool
    {
        return in_array($this->normalizeDetailLabel($label), $this->fabricLikeDetailLabels(), true);
    }

    private function preferredFabricDetailLabel(array $rows): string
    {
        foreach ($this->fabricLikeDetailLabels() as $label) {
            if (trim((string) ($rows[$label] ?? '')) !== '') {
                return $label;
            }
        }

        return 'Fabric';
    }

    private function detailValueForLabel(array $rows, string $label): string
    {
        $value = trim((string) ($rows[$label] ?? ''));

        if ($value !== '' || ! $this->isFabricLikeDetailLabel($label)) {
            return $value;
        }

        foreach ($this->fabricLikeDetailLabels() as $fabricLikeLabel) {
            $value = trim((string) ($rows[$fabricLikeLabel] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function parseDetailInformationText(string $text): array
    {
        $knownLabels = ['SKU', 'Product Type', 'Fabric', 'Material', 'Materials', 'Metarial', 'Metarials', 'Meterial', 'Meterials', 'Fabric GSM', 'GSM', 'Width', 'Fit', 'Customization', 'Imprint Method', 'Attachment', 'Size Range', 'MOQ', 'Lead Time', 'Shipping Time', 'Usage', 'Standard Length'];
        $rows = [];
        $lines = collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn ($line) => $this->cleanDetailInformationText($line))
            ->filter();

        foreach ($lines as $line) {
            if (preg_match('/^(detail|information|detail\s+information)$/i', $line)) {
                continue;
            }

            $flatRows = $this->parseFlatDetailInformationText($line, $knownLabels);
            if (count($flatRows) > 1) {
                foreach ($flatRows as $label => $value) {
                    $rows[$label] = $value;
                }
                continue;
            }

            if (str_contains($line, ':')) {
                [$label, $value] = array_pad(explode(':', $line, 2), 2, '');
                $label = $this->normalizeDetailLabel($label);
                if ($label !== '') {
                    $rows[$label] = $this->cleanDetailInformationText($value);
                }
            }
        }

        if ($rows === []) {
            $rows = $this->parseFlatDetailInformationText($text, $knownLabels);
        }

        return $rows;
    }

    private function parseFlatDetailInformationText(string $text, array $knownLabels): array
    {
        $flatText = $this->cleanDetailInformationText($text);
        if ($flatText === '') {
            return [];
        }

        $labelPattern = collect($knownLabels)->map(fn ($label) => preg_quote($label, '/'))->implode('|');
        if (! preg_match_all('/('.$labelPattern.')\s*:/i', $flatText, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $rows = [];
        $labelMatches = $matches[1];
        foreach ($labelMatches as $index => $match) {
            $label = $this->normalizeDetailLabel($match[0]);
            $start = $match[1] + strlen($match[0]);
            $end = isset($labelMatches[$index + 1]) ? $labelMatches[$index + 1][1] : strlen($flatText);
            $value = substr($flatText, $start, max(0, $end - $start));
            $value = preg_replace('/^\s*:\s*/', '', $value) ?? $value;

            if ($label !== '') {
                $rows[$label] = $this->cleanDetailInformationText($value);
            }
        }

        return $rows;
    }

    private function normalizeDetailLabel(?string $label): string
    {
        $clean = trim((string) $label, " \t\n\r\0\x0B:");
        $lookup = Str::lower($clean);

        return [
            'sku' => 'SKU',
            'style no' => 'SKU',
            'style number' => 'SKU',
            'product type' => 'Product Type',
            'product' => 'Product Type',
            'fabric' => 'Fabric',
            'fabric gsm' => 'Fabric GSM',
            'fabric weight' => 'Fabric GSM',
            'gsm' => 'GSM',
            'material' => 'Material',
            'materials' => 'Materials',
            'metarial' => 'Metarial',
            'metarials' => 'Metarials',
            'meterial' => 'Meterial',
            'meterials' => 'Meterials',
            'fit' => 'Fit',
            'width' => 'Width',
            'customization' => 'Customization',
            'customisation' => 'Customization',
            'printing' => 'Customization',
            'print method' => 'Customization',
            'imprint method' => 'Imprint Method',
            'decoration' => 'Customization',
            'attachment' => 'Attachment',
            'size range' => 'Size Range',
            'sizes' => 'Size Range',
            'size' => 'Size Range',
            'moq' => 'MOQ',
            'minimum order' => 'MOQ',
            'minimum order quantity' => 'MOQ',
            'lead time' => 'Lead Time',
            'lead-time' => 'Lead Time',
            'production time' => 'Lead Time',
            'shipping time' => 'Shipping Time',
            'usage' => 'Usage',
            'standard length' => 'Standard Length',
        ][$lookup] ?? Str::headline($clean);
    }

    private function cleanDetailInformationText(?string $value): string
    {
        return trim(preg_replace('/[ 	]+/', ' ', str_replace("\xc2\xa0", ' ', (string) $value)) ?? '');
    }

    private function hydrateProduct(array $product): array
    {
        $product['url'] = route('products.show', $product['slug']);
        $product['gallery'] = $product['gallery'] ?? [$product['image']];
        $product['customizable_options'] = $product['customizable_options'] ?? [$this->defaultDesignOption($product)];
        $product['size_quantity_groups'] = $product['size_quantity_groups'] ?? $this->defaultSizeQuantityGroups();
        $product['size_selector'] = $product['size_selector'] ?? $this->defaultSizeSelector($product);
        $product['size_chart'] = $product['size_chart'] ?? $this->defaultSizeChart($product);
        $product['price_tiers'] = $product['price_tiers'] ?? $this->defaultPriceTiers((float) ($product['base_price'] ?? 39));
        $product['detail_information'] = $product['detail_information'] ?? $this->defaultDetailInformation($product);
        $fallbackFabricLabel = $this->preferredFabricDetailLabel($product['detail_information'] ?? []);
        $product['summary_detail_information'] = $product['summary_detail_information'] ?? collect($product['detail_information'] ?? [])
            ->only(['SKU', 'Product Type', $fallbackFabricLabel, 'Fabric GSM', 'GSM', 'Width', 'Fit', 'Customization', 'Imprint Method', 'Attachment', 'Size Range', 'MOQ', 'Lead Time', 'Shipping Time', 'Usage', 'Standard Length'])
            ->filter(fn ($value) => filled($value))
            ->all();
        $product['details'] = $product['details'] ?? $this->legacyDetails($product);
        $product['option_steps'] = $product['option_steps'] ?? $this->defaultOptionSteps();
        $product['faqs'] = $product['faqs'] ?? $this->defaultFaqs();
        $product['product_profile'] = $product['product_profile'] ?? 'standard';
        $supportsSizeOptions = ProductSizing::supports($product['product_profile'] ?? 'standard');
        $product['is_featured'] = $product['is_featured'] ?? false;
        $product['is_customizable'] = $product['is_customizable'] ?? true;
        $product['currency'] = $product['currency'] ?? 'USD';
        $product['minimum_quantity'] = $product['minimum_quantity'] ?? 1;
        $product['maximum_quantity'] = $product['maximum_quantity'] ?? null;
        $product['description_html'] = $product['description_html'] ?? '<p>'.e($product['description']).'</p>';
        $product['customization_artwork_html'] = $product['customization_artwork_html'] ?? '';
        $product['fulfillment_html'] = $product['fulfillment_html'] ?? '';
        $product['option_groups'] = $product['option_groups'] ?? [];
        $product['size_groups'] = $supportsSizeOptions
            ? ($product['size_groups'] ?? collect($product['size_quantity_groups'])->map(fn ($group) => [
                'id' => $group['key'],
                'label' => $group['label'],
                'sizes' => collect($group['sizes'])->map(fn ($size) => ['code' => Str::slug($size), 'label' => $size, 'price_delta' => 0])->all(),
            ])->all())
            : [];
        $product['artwork_upload'] = $product['artwork_upload'] ?? [
            'enabled' => true,
            'required' => false,
            'title' => 'Upload Custom Artwork',
            'description' => 'Upload one or more artwork files for the production team.',
            'max_files' => 5,
            'max_file_size_mb' => 15,
            'accepted_types' => ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'],
        ];
        $product['artwork_methods'] = [];
        $product['shipping_methods'] = $product['shipping_methods'] ?? [];
        $product['production_methods_enabled'] = $product['production_methods_enabled'] ?? false;
        $product['shipping_methods_enabled'] = $product['shipping_methods_enabled'] ?? false;
        $product['jersey_roster'] = $product['jersey_roster'] ?? ['enabled' => false, 'optional' => true, 'title' => 'Add player names and numbers', 'fields' => []];
        $product['production_speeds'] = ($product['production_methods_enabled'] ?? false)
            ? ($product['production_speeds'] ?? [
                ['id' => 'standard', 'label' => 'Standard Production', 'description' => 'Standard schedule', 'price_delta' => 0, 'minimum_quantity' => 1, 'maximum_quantity' => null, 'minimum_days' => 14, 'maximum_days' => 18],
            ])
            : [];
        $product['price_table'] = $product['price_table'] ?? [
            'headers' => ['Quantity', 'Product Price', 'Shipping', 'Estimated Each', 'Order Total'],
            'rows' => collect($product['price_tiers'])->map(fn ($tier) => [$tier['quantity'], $tier['product_price'], $tier['shipping'], $tier['estimated_each'], $tier['estimated_order_total']])->all(),
            'highlight_column' => 3,
            'note' => 'Final pricing is confirmed after customization and artwork review.',
        ];
        $product['robots'] = $product['robots'] ?? 'index, follow';
        $product['meta_title'] = $product['meta_title'] ?? null;
        $product['meta_description'] = $product['meta_description'] ?? null;
        $product['canonical_url'] = $product['canonical_url'] ?? $product['url'];
        $product['og_title'] = $product['og_title'] ?? null;
        $product['og_description'] = $product['og_description'] ?? null;
        $product['og_image'] = $product['og_image'] ?? $product['image'];
        $product['review_items'] = collect($product['review_items'] ?? [])
            ->filter(fn ($review) => is_array($review))
            ->values()
            ->all();
        $product['review_distribution'] = $product['review_distribution']
            ?? $this->genuineReviewDistribution($product['review_items']);
        $product['gallery'] = collect($product['gallery'])->map(fn ($image) => is_array($image) ? $image : ['url' => $image, 'alt' => $product['alt']])->all();

        return $product;
    }

    private function products(): array
    {
        return [
            [
                'slug' => 'custom-cool-shapes-adult-youth-unisex-football-jersey',
                'title' => 'Custom Cool Shapes Adult Youth Unisex Football Jersey',
                'short_title' => 'Cool Shapes Football Jersey',
                'summary' => 'A fully customizable football jersey for adult and youth teams with design proof support, names, numbers, logos, and team colors.',
                'description' => 'Build a football jersey from a design template, then customize colors, logo placement, player names, roster numbers, fit notes, and artwork instructions before production.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-AYU-001',
                'rating' => 5,
                'reviews_count' => 34,
                'image' => asset('storage/storefront/home/football.webp'),
                'alt' => 'Custom football jersey with name and number',
                'gallery' => [
                    asset('storage/storefront/home/football.webp'),
                    asset('storage/storefront/home/baseball.webp'),
                    asset('storage/storefront/home/hero.webp'),
                ],
                'tags' => ['football jersey', 'cool shapes', 'custom football uniform', 'adult youth unisex', 'sublimation football jersey', 'team football uniform'],
                'features' => [
                    'Adult, women, youth, and toddler quantity entry',
                    'Name, number, logo, and roster options',
                    'Digital proof before production',
                    'Moisture-wicking polyester performance fabric',
                    'No minimum quantity for selected custom products',
                ],
                'detail_information' => [
                    'SKU' => 'NPS-FBL-JER-AYU-001',
                    'Product Type' => 'Football Jersey',
                    'Fabric' => '220gsm Pro-Wick Polyester Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'V-Neck / Football Collar',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Football',
            ],
            [
                'slug' => 'custom-football-jersey-name-number',
                'title' => 'Custom Football Jersey with Name & Number',
                'short_title' => 'Football Jersey',
                'summary' => 'Personalized football jersey with player name, number, team colors, and optional logo placement.',
                'description' => 'A clean football jersey product for teams, fans, school events, and player uniforms.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-NN-002',
                'rating' => 5,
                'reviews_count' => 28,
                'image' => asset('storage/storefront/home/football.webp'),
                'alt' => 'Custom football jersey',
                'tags' => ['football jersey', 'custom name number', 'team jersey', 'sportswear'],
                'features' => ['Player name and number', 'Team color direction', 'Logo placement notes', 'Team roster support'],
                'brand' => 'Football',
            ],
            [
                'slug' => 'baseball-uniform-set-for-teams',
                'title' => 'Baseball Uniform Set for Teams',
                'short_title' => 'Baseball Uniform Set',
                'summary' => 'Coordinated baseball jersey and uniform set for school, club, and league team orders.',
                'description' => 'Team-ready baseball uniform set with quote-based sizing, roster, and design review.',
                'price' => 'Request Quote',
                'base_price' => 49,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Baseball',
                'category' => 'Baseball Uniforms',
                'sku' => 'NPS-BSB-UNI-SET-003',
                'rating' => 5,
                'reviews_count' => 21,
                'image' => asset('storage/storefront/home/baseball.webp'),
                'alt' => 'Baseball uniform set',
                'tags' => ['baseball uniform', 'team set', 'jersey pants', 'club uniform'],
                'features' => ['Team roster sizing', 'Jersey and pants direction', 'Logo and number support', 'Bulk quote ready'],
                'brand' => 'Baseball',
            ],
            [
                'slug' => 'custom-basketball-jersey',
                'title' => 'Custom Basketball Jersey',
                'short_title' => 'Basketball Jersey',
                'summary' => 'Breathable basketball jersey with team color, name, number, and logo customization.',
                'description' => 'A lightweight basketball jersey suitable for clubs, tournaments, training, and fan gear.',
                'price' => 'From $34',
                'base_price' => 34,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Basketball',
                'category' => 'Basketball Jerseys',
                'sku' => 'NPS-BSK-JER-UR-001',
                'rating' => 5,
                'reviews_count' => 17,
                'image' => asset('storage/storefront/home/basketball.webp'),
                'alt' => 'Custom basketball jersey',
                'tags' => ['basketball club uniform', 'birdseye mesh jersey', 'breathable basketball jersey', 'Custom Basketball Jersey', 'custom sportswear', 'custom team jersey', 'elite basketball jersey', 'round neck basketball jersey', 'sublimation basketball jersey', 'team basketball uniform', 'unisex basketball jersey', 'youth basketball jersey'],
                'features' => ['Sleeveless jersey style', 'Name and number support', 'Team color direction', 'Bulk team order ready'],
                'detail_information' => [
                    'SKU' => 'NPS-BSK-JER-UR-001',
                    'Product Type' => 'Basketball Jersey',
                    'Fabric' => '160gsm Birdseye Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'Round Neck',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Basketball',
                'size_chart' => [
                    'title' => 'Basketball Jersey Size Chart',
                    'note' => 'Measurements are garment guidance. Choose one size up for a looser game fit.',
                    'groups' => [
                        [
                            'label' => 'Adult Unisex',
                            'columns' => ['Size', 'Chest', 'Length', 'Shoulder'],
                            'rows' => [
                                ['XS', '32-34"', '27"', '15.5"'],
                                ['S', '34-37"', '28"', '16.5"'],
                                ['M', '37-40"', '29"', '17.5"'],
                                ['L', '40-43"', '30"', '18.5"'],
                                ['XL', '43-46"', '31"', '19.5"'],
                                ['2XL', '46-49"', '32"', '20.5"'],
                                ['3XL', '49-52"', '33"', '21.5"'],
                                ['4XL', '52-55"', '34"', '22.5"'],
                            ],
                        ],
                        [
                            'label' => 'Youth Unisex',
                            'columns' => ['Size', 'Age', 'Chest', 'Length'],
                            'rows' => [
                                ['YXS', '4-5', '23-24"', '20"'],
                                ['YS', '6-7', '25-26"', '21"'],
                                ['YM', '8-9', '27-28"', '23"'],
                                ['YL', '10-11', '29-31"', '25"'],
                                ['YXL', '12-14', '32-34"', '27"'],
                            ],
                        ],
                    ],
                    'tips' => ['For team orders, collect sizes before submitting the roster.', 'Custom sizing can be requested in the notes field.'],
                ],
            ],
            [
                'slug' => 'sublimated-soccer-kit-for-teams',
                'title' => 'Sublimated Soccer Kit',
                'short_title' => 'Soccer Kit',
                'summary' => 'Custom soccer kit for clubs, school teams, and fan groups with jersey and team details.',
                'description' => 'Soccer kit page with custom logo, player list, and quote-oriented production support.',
                'price' => 'Request Quote',
                'base_price' => 42,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Soccer',
                'category' => 'Soccer Kits',
                'sku' => 'NPS-SCR-KIT-TM-005',
                'rating' => 5,
                'reviews_count' => 24,
                'image' => asset('storage/storefront/home/soccer.webp'),
                'alt' => 'Sublimated soccer kit',
                'tags' => ['soccer kit', 'uniform', 'club team', 'sublimated'],
                'features' => ['Club and school team ready', 'Logo and sponsor notes', 'Player roster support', 'Bulk sizing table support'],
                'brand' => 'Soccer',
            ],
            [
                'slug' => 'custom-team-hoodie',
                'title' => 'Custom Team Hoodie',
                'short_title' => 'Team Hoodie',
                'summary' => 'Team hoodie for travel, sideline, spirit wear, coaches, staff, and fan apparel.',
                'description' => 'Custom hoodie with logo, embroidery/print method, and bulk ordering support.',
                'price' => 'From $45',
                'base_price' => 45,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Training',
                'category' => 'Apparel',
                'sku' => 'NPS-HOD-APP-TM-006',
                'rating' => 5,
                'reviews_count' => 18,
                'image' => asset('storage/storefront/home/hoodies.webp'),
                'alt' => 'Custom team hoodie',
                'tags' => ['hoodie', 'apparel', 'team', 'spirit wear'],
                'features' => ['Team logo support', 'Front/back print notes', 'Bulk apparel pricing', 'Coach and fan gear ready'],
                'brand' => 'Team Apparel',
            ],
            [
                'slug' => 'custom-embroidered-cap',
                'title' => 'Custom Embroidered Cap',
                'short_title' => 'Embroidered Cap',
                'summary' => 'Custom cap with embroidered logo direction for teams, businesses, events, and fan shops.',
                'description' => 'Cap customization with logo file upload, embroidery position notes, and bulk pricing support.',
                'price' => 'From $18',
                'base_price' => 18,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Accessories',
                'category' => 'Caps & Hats',
                'sku' => 'NPS-CAP-EMB-007',
                'rating' => 5,
                'reviews_count' => 14,
                'image' => asset('storage/storefront/home/caps.webp'),
                'alt' => 'Custom embroidered cap',
                'tags' => ['cap', 'hat', 'embroidery', 'team logo'],
                'features' => ['Front logo placement', 'Embroidery quote support', 'Event and team order ready', 'Bulk available'],
                'brand' => 'Accessories',
            ],
            [
                'slug' => 'personalized-sports-duffel-bag',
                'title' => 'Personalized Sports Duffel Bag',
                'short_title' => 'Sports Duffel Bag',
                'summary' => 'Personalized sports bag for team travel, gym use, events, and promotional programs.',
                'description' => 'Duffel bag product page with logo, name, and bulk promotional order notes.',
                'price' => 'From $32',
                'base_price' => 32,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Accessories',
                'category' => 'Bags',
                'sku' => 'NPS-BAG-DUF-008',
                'rating' => 5,
                'reviews_count' => 12,
                'image' => asset('storage/storefront/home/bags.webp'),
                'alt' => 'Personalized sports duffel bag',
                'tags' => ['bag', 'duffel', 'team', 'travel'],
                'features' => ['Team logo direction', 'Player name notes', 'Travel-friendly product', 'Promotional order support'],
                'brand' => 'Bags',
            ],
            [
                'slug' => 'custom-fan-jersey',
                'title' => 'Custom Fan Jersey',
                'short_title' => 'Fan Jersey',
                'summary' => 'Personalized fan jersey for supporters, school events, tournaments, and spirit wear.',
                'description' => 'Custom fan jersey with name, number, color, and event-specific design support.',
                'price' => 'From $36',
                'base_price' => 36,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Fan Gear',
                'category' => 'Custom Jerseys',
                'sku' => 'NPS-FAN-JER-009',
                'rating' => 5,
                'reviews_count' => 16,
                'image' => asset('storage/storefront/home/training.webp'),
                'alt' => 'Custom fan jersey',
                'tags' => ['fan jersey', 'customizable', 'spirit wear', 'event'],
                'features' => ['Fan name and number', 'Event color direction', 'Spirit wear ready', 'Small and bulk order support'],
                'brand' => 'Fan Gear',
            ],
        ];
    }

    private function defaultDesignOption(array $product = []): array
    {
        return [
            'id' => 'design-style',
            'step' => '01',
            'title' => 'Choose Design Style',
            'subtitle' => 'Pick a starting point',
            'description' => 'One admin-defined customizable option is shown now. The same reusable component can render more options later.',
            'type' => 'visual-radio',
            'required' => true,
            'help' => 'Final colors, logos, names, numbers, and artwork can be adjusted after proof review.',
            'values' => [
                [
                    'label' => 'Default Team Style',
                    'value' => 'default-team-style',
                    'price_delta' => '$0',
                    'badge' => 'Default',
                    'preview' => 'linear-gradient(135deg,#15345d 0%,#2467b7 50%,#e91d33 50%,#e91d33 100%)',
                    'description' => 'Clean team look using the product default template.',
                ],
                [
                    'label' => 'Modern Graphic',
                    'value' => 'modern-graphic',
                    'price_delta' => '+$3',
                    'badge' => 'Popular',
                    'preview' => 'radial-gradient(circle at 30% 30%,#e91d33,#2467b7 45%,#0d2545 100%)',
                    'description' => 'Bolder modern design with high-impact color movement.',
                ],
                [
                    'label' => 'Upload Custom Artwork',
                    'value' => 'upload-custom-artwork',
                    'price_delta' => 'Quote',
                    'badge' => 'Custom',
                    'preview' => 'linear-gradient(45deg,#f8fafc 25%,#e2e8f0 25%,#e2e8f0 50%,#f8fafc 50%,#f8fafc 75%,#e2e8f0 75%)',
                    'description' => 'Upload your own artwork or ask the design team to recreate it.',
                ],
            ],
        ];
    }

    private function defaultSizeSelector(array $product): array
    {
        return [
            'step' => '07',
            'title' => 'Choose Your Sizes',
            'note' => $product['sport'] === 'Football'
                ? 'We suggest going up one size if wearing on top of shoulder or elbow pads.'
                : 'Choose the best fit for each player. Add custom size notes if needed.',
            'base_price' => (float) ($product['base_price'] ?? 39),
        ];
    }

    private function defaultSizeQuantityGroups(): array
    {
        return [
            [
                'key' => 'men',
                'label' => 'Men',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', 'CUSTOM'],
            ],
            [
                'key' => 'women',
                'label' => 'Women',
                'sizes' => ['WS', 'WM', 'WL', 'WXL', 'W2XL', 'W3XL', 'W4XL', 'CUSTOM'],
            ],
            [
                'key' => 'youth',
                'label' => 'Youth',
                'sizes' => ['YXS', 'YS', 'YM', 'YL', 'YXL', 'CUSTOM'],
            ],
            [
                'key' => 'toddler',
                'label' => 'Toddler',
                'sizes' => ['2T', '3T', '4T', 'CUSTOM'],
            ],
        ];
    }

    private function defaultOptionSteps(): array
    {
        return [
            ['title' => 'Choose Apparel', 'description' => 'Select the jersey, uniform set, hoodie, cap, bag, or accessory variation.'],
            ['title' => 'Choose Design', 'description' => 'Pick a template, modern graphic, or custom artwork direction.'],
            ['title' => 'Upload Artwork', 'description' => 'Attach logo, roster, design sample, or old order reference.'],
            ['title' => 'Choose Sizes', 'description' => 'Enter size quantities for men, women, youth, toddler, or custom sizes.'],
            ['title' => 'Review Proof', 'description' => 'Approve spelling, artwork placement, colors, and final layout before production.'],
            ['title' => 'Confirm Order', 'description' => 'Production and delivery timeline is confirmed after proof approval.'],
        ];
    }

    private function defaultDetailInformation(array $product): array
    {
        $sport = $product['sport'] ?? 'Sportswear';
        $type = Str::contains(Str::lower($product['title'] ?? ''), 'hoodie') ? 'Team Hoodie' : ($sport . ' Product');

        if (Str::contains(Str::lower($product['title'] ?? ''), 'jersey')) {
            $type = $sport . ' Jersey';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'kit')) {
            $type = $sport . ' Kit';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'cap')) {
            $type = 'Embroidered Cap';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'bag')) {
            $type = 'Duffel Bag';
        }

        return [
            'Product Type' => $type,
            'Fabric' => $this->defaultFabricFor($product),
            'Collection Tier' => 'Elite',
            'Neckline' => Str::contains(Str::lower($type), 'hoodie') ? 'Hooded' : 'Sport-specific neckline',
            'Customization' => 'Full Sublimation / Print / Embroidery Quote',
            'MOQ' => '1 Piece',
            'Lead Time' => '18–22 business days + shipping',
        ];
    }

    private function legacyDetails(array $product): array
    {
        return [
            'Category' => $product['category'] ?? 'Custom Sportswear',
            'Order Type' => 'Custom / bulk / quote-ready',
            'Proof' => 'Digital proof before production',
            'Artwork' => 'PNG, JPG, PDF, AI, or design notes',
            'Production' => 'Timeline confirmed after proof approval',
        ];
    }

    private function defaultFabricFor(array $product): string
    {
        $sport = Str::lower($product['sport'] ?? '');
        $title = Str::lower($product['title'] ?? '');

        return match (true) {
            Str::contains($sport, 'basketball') => '160gsm Birdseye Mesh',
            Str::contains($sport, 'football') => '220gsm Pro-Wick Polyester Mesh',
            Str::contains($sport, 'soccer') => '180gsm Dry-Fit Polyester',
            Str::contains($title, 'hoodie') => 'Fleece Cotton-Poly Blend',
            Str::contains($title, 'cap') => 'Structured Cotton Twill',
            Str::contains($title, 'bag') => 'Heavy-Duty Polyester',
            default => 'Performance Polyester',
        };
    }

    private function defaultPriceTiers(float $basePrice): array
    {
        $tiers = [
            ['qty' => '1-5', 'discount' => 0, 'shipping' => 8, 'note' => 'Small custom order'],
            ['qty' => '6-24', 'discount' => 0.08, 'shipping' => 6, 'note' => 'Small team order'],
            ['qty' => '25-99', 'discount' => 0.15, 'shipping' => 5, 'note' => 'Team pricing'],
            ['qty' => '100+', 'discount' => 0.22, 'shipping' => 4, 'note' => 'Bulk quote recommended'],
        ];

        return array_map(function (array $tier) use ($basePrice): array {
            $productPrice = round($basePrice * (1 - $tier['discount']), 2);

            return [
                'quantity' => $tier['qty'],
                'product_price' => '$' . number_format($productPrice, 2),
                'shipping' => '$' . number_format($tier['shipping'], 2),
                'estimated_each' => '$' . number_format($productPrice + $tier['shipping'], 2),
                'estimated_order_total' => $tier['qty'] === '100+'
                    ? 'Quote'
                    : '$' . number_format(($productPrice + $tier['shipping']) * (int) explode('-', $tier['qty'])[0], 2) . '+',
                'note' => $tier['note'],
            ];
        }, $tiers);
    }

    private function defaultSizeChart(array $product): array
    {
        return [
            'title' => ($product['sport'] ?? 'Product') . ' Size Chart',
            'note' => 'Measurements are garment guidance. Allow small production tolerance for custom apparel.',
            'groups' => [
                [
                    'label' => 'Adult Unisex',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['XS', '32-34"', '27"', '16.5"', '8.5"'],
                        ['S', '34-37"', '28"', '17.5"', '9"'],
                        ['M', '37-40"', '29"', '18.5"', '9.5"'],
                        ['L', '40-43"', '30"', '19.5"', '10"'],
                        ['XL', '43-46"', '31"', '20.5"', '10.5"'],
                        ['2XL', '46-49"', '32"', '21.5"', '11"'],
                        ['3XL', '49-52"', '33"', '22.5"', '11.5"'],
                        ['4XL', '52-55"', '34"', '23.5"', '12"'],
                    ],
                ],
                [
                    'label' => 'Women',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['WS', '30-32"', '25"', '14.5"', '7.5"'],
                        ['WM', '32-35"', '26"', '15.5"', '8"'],
                        ['WL', '35-38"', '27"', '16.5"', '8.5"'],
                        ['WXL', '38-41"', '28"', '17.5"', '9"'],
                        ['W2XL', '41-44"', '29"', '18.5"', '9.5"'],
                        ['W3XL', '44-47"', '30"', '19.5"', '10"'],
                    ],
                ],
                [
                    'label' => 'Youth Unisex',
                    'columns' => ['Size', 'Age', 'Chest', 'Length', 'Height'],
                    'rows' => [
                        ['YXS', '4-5', '23-24"', '20"', '100-110 cm'],
                        ['YS', '6-7', '25-26"', '21"', '110-122 cm'],
                        ['YM', '8-9', '27-28"', '23"', '122-135 cm'],
                        ['YL', '10-11', '29-31"', '25"', '135-150 cm'],
                        ['YXL', '12-14', '32-34"', '27"', '150-163 cm'],
                    ],
                ],
                [
                    'label' => 'Toddler',
                    'columns' => ['Size', 'Age', 'Chest', 'Length'],
                    'rows' => [
                        ['2T', '2 years', '20-21"', '15"'],
                        ['3T', '3 years', '21-22"', '16"'],
                        ['4T', '4 years', '22-23"', '17"'],
                    ],
                ],
            ],
            'tips' => [
                'For a looser fit, choose one size up.',
                'For team orders, collect sizes before submitting the roster.',
                'Custom sizing can be requested in the order notes.',
            ],
        ];
    }

    private function defaultFaqs(): array
    {
        return [
            [
                'question' => 'Can the admin add more customizable options later?',
                'answer' => 'Yes. The product page loops through customizable option groups, so the admin can later add fabric, print method, collar, sleeve fit, belt, delivery, or accessories using the same reusable component.',
            ],
            [
                'question' => 'Can customers upload artwork?',
                'answer' => 'Yes. The product page includes an artwork upload field and order notes field. Backend upload processing can be connected when the cart/order module is ready.',
            ],
            [
                'question' => 'Will customers see a proof before production?',
                'answer' => 'The page is designed around proof review. Production should start only after the customer approves the final design proof.',
            ],
        ];
    }
}
