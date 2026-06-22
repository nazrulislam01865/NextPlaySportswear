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
                'children' => fn (Builder $query) => $query->storefrontReachable(),
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

        // Aggregate category-specific ordering before joining products. This avoids
        // duplicate products when an item belongs to both a parent and a child category.
        $listing = DB::table('category_product')
            ->selectRaw('product_id, MAX(is_featured) AS category_featured, MIN(sort_order) AS category_sort')
            ->whereIn('category_id', $categoryIds)
            ->groupBy('product_id');

        $query = Product::query()
            ->published()
            ->joinSub($listing, 'listing_cp', fn ($join) => $join->on('listing_cp.product_id', '=', 'products.id'))
            ->select(['products.*', 'listing_cp.category_featured', 'listing_cp.category_sort'])
            ->with($this->productRelations());

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
                $query->whereHas('categories', fn (Builder $builder) => $builder->whereIn('categories.id', $selected));
            }
        }

        $allowedAttributeSlugs = $category->filters
            ->filter(fn (CatalogAttribute $attribute): bool => $attribute->is_active && $attribute->is_filterable)
            ->pluck('slug')
            ->all();

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
                ->orderByDesc('listing_cp.category_featured')
                ->orderBy('listing_cp.category_sort')
                ->orderByDesc('products.is_featured')
                ->orderBy('products.sort_order')
                ->orderBy('products.name'),
        };

        $paginator = $query->paginate(config('catalog.category_page_size', 24))->withQueryString();
        $paginator->through(fn (Product $product): array => $this->productCatalogService->fromModel($product));

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

            $valueCounts = DB::table('attribute_value_product as avp')
                ->join('attribute_values as av', 'av.id', '=', 'avp.attribute_value_id')
                ->join('products as p', 'p.id', '=', 'avp.product_id')
                ->join('category_product as cp', 'cp.product_id', '=', 'p.id')
                ->whereIn('cp.category_id', $categoryIds)
                ->where('p.status', 'active')
                ->where('p.is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('p.published_at')->orWhere('p.published_at', '<=', now());
                })
                ->where('av.is_active', true)
                ->selectRaw('avp.attribute_value_id, COUNT(DISTINCT p.id) AS aggregate')
                ->groupBy('avp.attribute_value_id')
                ->pluck('aggregate', 'avp.attribute_value_id');

            $attributes = $category->filters
                ->filter(fn (CatalogAttribute $attribute) => $attribute->is_active && $attribute->is_filterable)
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
                        'name' => $attribute->pivot->label ?: $attribute->name,
                        'slug' => $attribute->slug,
                        'display_type' => $attribute->display_type,
                        'is_expanded' => (bool) $attribute->pivot->is_expanded,
                        'values' => $values,
                    ];
                })
                ->filter(fn (array $attribute) => $attribute['values'] !== [])
                ->values()
                ->all();

            $priceCeiling = (float) Product::query()
                ->published()
                ->whereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $categoryIds))
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
        if (! $category->include_descendant_products) {
            return [$category->id];
        }

        $descendantIds = $this->treeService->descendantIds($category->id, true);

        return Category::query()
            ->storefrontReachable()
            ->whereIn('id', $descendantIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function productCount(Category $category): int
    {
        $ids = $this->categoryIdsForListing($category);

        return Product::query()
            ->published()
            ->whereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $ids))
            ->count();
    }

    /** @param Collection<int, Category> $categories */
    private function attachProductCounts(Collection $categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        $ids = $categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $directCounts = DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->whereIn('cp.category_id', $ids)
            ->where('p.status', 'active')
            ->where('p.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('p.published_at')->orWhere('p.published_at', '<=', now());
            })
            ->selectRaw('cp.category_id, COUNT(DISTINCT p.id) AS aggregate')
            ->groupBy('cp.category_id')
            ->pluck('aggregate', 'cp.category_id');

        $descendantCounts = DB::table('category_closure as cc')
            ->join('categories as dc', 'dc.id', '=', 'cc.descendant_id')
            ->join('category_product as cp', 'cp.category_id', '=', 'dc.id')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->whereIn('cc.ancestor_id', $ids)
            ->whereNull('dc.deleted_at')
            ->where('dc.is_active', true)
            ->where('dc.status', 'active')
            ->where('dc.is_visible_in_catalog', true)
            ->where(function ($query): void {
                $query->whereNull('dc.published_at')->orWhere('dc.published_at', '<=', now());
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('category_closure as blocked_cc')
                    ->join('categories as blocked_category', 'blocked_category.id', '=', 'blocked_cc.ancestor_id')
                    ->whereColumn('blocked_cc.descendant_id', 'dc.id')
                    ->where('blocked_cc.depth', '>', 0)
                    ->where(function ($blocked): void {
                        $blocked->whereNotNull('blocked_category.deleted_at')
                            ->orWhere('blocked_category.is_active', false)
                            ->orWhere('blocked_category.status', '!=', 'active')
                            ->orWhere('blocked_category.is_visible_in_catalog', false)
                            ->orWhere(function ($scheduled): void {
                                $scheduled->whereNotNull('blocked_category.published_at')
                                    ->where('blocked_category.published_at', '>', now());
                            });
                    });
            })
            ->where('p.status', 'active')
            ->where('p.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('p.published_at')->orWhere('p.published_at', '<=', now());
            })
            ->selectRaw('cc.ancestor_id, COUNT(DISTINCT p.id) AS aggregate')
            ->groupBy('cc.ancestor_id')
            ->pluck('aggregate', 'cc.ancestor_id');

        foreach ($categories as $category) {
            $count = $category->include_descendant_products
                ? (int) ($descendantCounts[$category->id] ?? 0)
                : (int) ($directCounts[$category->id] ?? 0);
            $category->setAttribute('products_count', $count);
        }
    }

    private function productRelations(): array
    {
        return [
            'category', 'subcategory', 'categories', 'attributeValues.attribute', 'images',
            'optionGroups.values', 'sizeGroups.sizes', 'priceTiers', 'artworkMethods',
            'productionSpeeds', 'faqs',
        ];
    }
}
