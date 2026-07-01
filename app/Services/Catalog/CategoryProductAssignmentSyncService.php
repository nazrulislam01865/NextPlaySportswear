<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryProductAssignmentSyncService
{
    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly NavigationService $navigationService,
    ) {
    }

    /**
     * Rebuild the storefront category assignment table from trusted product data.
     *
     * The previous broad text matcher could assign every product to unrelated
     * categories such as Bags or Fan Gear because words like "sports", "team",
     * or "jersey" appeared in many products. This repair is intentionally more
     * strict:
     * - optionally clears the existing category_product rows first;
     * - rebuilds from products.category_id and products.subcategory_id;
     * - adds parent/ancestor category rows so parent menu categories show products;
     * - adds only safe rule-based categories from explicit category match_rules;
     * - never changes the trusted leaf category primary unless a product has no
     *   legacy category at all.
     *
     * @return array<string, int>
     */
    public function syncAllProductCategoryAssignments(bool $resetExisting = true): array
    {
        $this->treeService->rebuildClosure();

        $stats = [
            'total_products_in_table' => (int) Product::withTrashed()->count(),
            'published_products_in_table' => (int) Product::query()->published()->count(),
            'assignments_deleted' => 0,
            'products_scanned' => 0,
            'products_with_legacy_category' => 0,
            'legacy_assignments_created' => 0,
            'ancestor_assignments_created' => 0,
            'trusted_rule_products_scanned' => 0,
            'trusted_rule_products_matched' => 0,
            'trusted_rule_assignments_created' => 0,
            'trusted_rule_ancestor_assignments_created' => 0,
            'assignments_existing' => 0,
            'primary_fixed' => 0,
            'invalid_category_references' => 0,
            'products_without_category' => 0,
        ];

        DB::transaction(function () use (&$stats, $resetExisting): void {
            if ($resetExisting) {
                $stats['assignments_deleted'] = (int) DB::table('category_product')->count();
                DB::table('category_product')->delete();
            }

            $this->syncFromLegacyProductCategories($stats);
            $this->syncFromTrustedMatchRules($stats);
            $this->ensureSinglePrimaryPerProduct($stats);
        });

        $this->flushCatalogCaches();

        return $stats;
    }

    /** @param array<string, int> $stats */
    private function syncFromLegacyProductCategories(array &$stats): void
    {
        $categories = Category::query()->get(['id', 'parent_id']);
        $validCategoryIds = $categories->pluck('id')->map(fn ($id): int => (int) $id)->flip();
        $parentById = $categories->pluck('parent_id', 'id')
            ->map(fn ($parentId): ?int => $parentId === null ? null : (int) $parentId);

        Product::withTrashed()
            ->select(['id', 'category_id', 'subcategory_id', 'sort_order'])
            ->orderBy('id')
            ->chunkById(300, function ($products) use (&$stats, $validCategoryIds, $parentById): void {
                foreach ($products as $product) {
                    $stats['products_scanned']++;

                    $legacyCategoryIds = collect([$product->category_id, $product->subcategory_id])
                        ->filter(fn ($id): bool => $id !== null && (int) $id > 0)
                        ->map(fn ($id): int => (int) $id)
                        ->unique()
                        ->values();

                    if ($legacyCategoryIds->isEmpty()) {
                        $stats['products_without_category']++;
                        continue;
                    }

                    $stats['products_with_legacy_category']++;

                    $validLegacyCategoryIds = $legacyCategoryIds
                        ->filter(fn (int $categoryId): bool => $validCategoryIds->has($categoryId))
                        ->values();

                    $stats['invalid_category_references'] += $legacyCategoryIds->count() - $validLegacyCategoryIds->count();

                    if ($validLegacyCategoryIds->isEmpty()) {
                        continue;
                    }

                    $legacyPrimaryId = (int) ($product->subcategory_id ?: $product->category_id);
                    if (! $validCategoryIds->has($legacyPrimaryId)) {
                        $legacyPrimaryId = (int) $validLegacyCategoryIds->first();
                    }

                    $assignmentIds = $validLegacyCategoryIds
                        ->flatMap(fn (int $categoryId): array => $this->categoryWithAncestorIds($categoryId, $parentById))
                        ->filter(fn (int $categoryId): bool => $validCategoryIds->has($categoryId))
                        ->unique()
                        ->values();

                    foreach ($assignmentIds as $categoryId) {
                        $created = $this->upsertAssignment(
                            productId: (int) $product->id,
                            categoryId: (int) $categoryId,
                            isPrimary: (int) $categoryId === $legacyPrimaryId,
                            sortOrder: (int) ($product->sort_order ?? 0),
                            allowPrimaryPromotion: true,
                        );

                        if ($created) {
                            $stats['legacy_assignments_created']++;
                            if (! $validLegacyCategoryIds->contains((int) $categoryId)) {
                                $stats['ancestor_assignments_created']++;
                            }
                        } else {
                            $stats['assignments_existing']++;
                        }
                    }
                }
            });
    }

    /** @param array<string, int> $stats */
    private function syncFromTrustedMatchRules(array &$stats): void
    {
        $categories = Category::query()
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id', 'name', 'menu_label', 'slug', 'category_type', 'match_rules']);

        if ($categories->isEmpty()) {
            return;
        }

        $validCategoryIds = $categories->pluck('id')->map(fn ($id): int => (int) $id)->flip();
        $categoriesById = $categories->keyBy('id');
        $parentById = $categories->pluck('parent_id', 'id')
            ->map(fn ($parentId): ?int => $parentId === null ? null : (int) $parentId);
        $ruleCategories = $this->trustedRuleCategoryMap($categories);

        if ($ruleCategories === []) {
            return;
        }

        Product::withTrashed()
            ->select([
                'id', 'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'product_type', 'brand',
                'short_description', 'description_html', 'features', 'specifications', 'tags', 'sort_order',
            ])
            ->orderBy('id')
            ->chunkById(300, function ($products) use (&$stats, $ruleCategories, $validCategoryIds, $categoriesById, $parentById): void {
                foreach ($products as $product) {
                    $stats['trusted_rule_products_scanned']++;

                    $text = $this->productSearchText($product);
                    $legacyNames = $this->legacyCategoryNamesForProduct($product, $categoriesById, $parentById);
                    $matchedIds = collect();

                    foreach ($ruleCategories as $categoryId => $rules) {
                        if ($this->rulesMatchProduct($rules, $text, $legacyNames)) {
                            $matchedIds->push((int) $categoryId);
                        }
                    }

                    $matchedIds = $matchedIds
                        ->filter(fn (int $categoryId): bool => $validCategoryIds->has($categoryId))
                        ->unique()
                        ->values();

                    if ($matchedIds->isEmpty()) {
                        continue;
                    }

                    $stats['trusted_rule_products_matched']++;

                    $assignmentIds = $matchedIds
                        ->flatMap(fn (int $categoryId): array => $this->categoryWithAncestorIds($categoryId, $parentById))
                        ->filter(fn (int $categoryId): bool => $validCategoryIds->has($categoryId))
                        ->unique()
                        ->values();

                    foreach ($assignmentIds as $categoryId) {
                        $created = $this->upsertAssignment(
                            productId: (int) $product->id,
                            categoryId: (int) $categoryId,
                            isPrimary: false,
                            sortOrder: (int) ($product->sort_order ?? 0),
                            allowPrimaryPromotion: false,
                        );

                        if ($created) {
                            $stats['trusted_rule_assignments_created']++;
                            if (! $matchedIds->contains((int) $categoryId)) {
                                $stats['trusted_rule_ancestor_assignments_created']++;
                            }
                        } else {
                            $stats['assignments_existing']++;
                        }
                    }
                }
            });
    }

    /**
     * @param Collection<int, Category> $categories
     * @return array<int, array{categories: array<int, string>, sports: array<int, string>, tag_terms: array<int, string>}>
     */
    private function trustedRuleCategoryMap(Collection $categories): array
    {
        $map = [];

        foreach ($categories as $category) {
            $rules = $category->match_rules;
            if (is_string($rules)) {
                $rules = json_decode($rules, true) ?: [];
            }
            if (! is_array($rules) || isset($rules['legacy_wp_term_id'])) {
                continue;
            }

            $categoryTerms = $this->normalizeTermList($rules['categories'] ?? []);
            $sports = $this->normalizeTermList($rules['sports'] ?? []);
            $tagTerms = $this->normalizeTermList($rules['tag_terms'] ?? []);

            // Keep only useful, explicit terms. This prevents broad terms from
            // assigning products to unrelated categories. Sport terms are only
            // trusted for sport landing categories; a regular category such as
            // "Soccer Kits" should not receive every soccer product just because
            // it has a broad sport hint in its rule JSON.
            $sports = ((string) $category->category_type === 'sport' ? $sports : collect())
                ->filter(fn (string $term): bool => $this->isSafeRuleTerm($term, allowSingleWordSports: true))
                ->values();
            $tagTerms = $tagTerms->filter(fn (string $term): bool => $this->isSafeRuleTerm($term, allowSingleWordSports: false))->values();
            $categoryTerms = $categoryTerms->filter(fn (string $term): bool => $this->isSafeRuleTerm($term, allowSingleWordSports: false))->values();

            if ($categoryTerms->isEmpty() && $sports->isEmpty() && $tagTerms->isEmpty()) {
                continue;
            }

            $map[(int) $category->id] = [
                'categories' => $categoryTerms->all(),
                'sports' => $sports->all(),
                'tag_terms' => $tagTerms->all(),
            ];
        }

        return $map;
    }

    /** @return Collection<int, string> */
    private function normalizeTermList(mixed $value): Collection
    {
        return collect(is_array($value) ? $value : [$value])
            ->map(fn ($term): string => $this->normalizeText((string) $term))
            ->filter()
            ->unique()
            ->values();
    }

    private function isSafeRuleTerm(string $term, bool $allowSingleWordSports): bool
    {
        if ($term === '' || mb_strlen($term) < 3) {
            return false;
        }

        $blocked = [
            'custom', 'product', 'products', 'sportswear', 'sports', 'sport', 'team', 'teams',
            'wear', 'performance', 'collection', 'collections', 'new', 'sale', 'all', 'nextplay',
            'apparel', 'accessory', 'accessories', 'gear', 'event', 'events', 'bulk',
        ];

        if (in_array($term, $blocked, true)) {
            return false;
        }

        $wordCount = count(array_filter(explode(' ', $term)));
        if ($wordCount >= 2) {
            return true;
        }

        return $allowSingleWordSports && in_array($term, [
            'football', 'baseball', 'basketball', 'soccer', 'softball', 'volleyball',
            'cheerleading', 'training', 'fitness', 'hockey', 'track', 'field',
        ], true)
            || in_array($term, ['jersey', 'jerseys', 'hoodie', 'sweatshirt', 'cap', 'hat', 'polo', 'shorts', 'jacket', 'outerwear'], true);
    }

    /** @return Collection<int, string> */
    private function legacyCategoryNamesForProduct(Product $product, Collection $categoriesById, Collection $parentById): Collection
    {
        return collect([$product->category_id, $product->subcategory_id])
            ->filter(fn ($id): bool => $id !== null && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->flatMap(fn (int $categoryId): array => $this->categoryWithAncestorIds($categoryId, $parentById))
            ->map(fn (int $categoryId) => $categoriesById->get($categoryId))
            ->filter()
            ->flatMap(function (Category $category): array {
                $name = $this->normalizeText((string) $category->name);
                $slug = $this->normalizeText(str_replace('-', ' ', (string) $category->slug));
                $menu = $this->normalizeText((string) $category->menu_label);

                return array_values(array_unique(array_filter([$name, $slug, $menu])));
            })
            ->unique()
            ->values();
    }

    /**
     * @param array{categories: array<int, string>, sports: array<int, string>, tag_terms: array<int, string>} $rules
     * @param Collection<int, string> $legacyNames
     */
    private function rulesMatchProduct(array $rules, string $text, Collection $legacyNames): bool
    {
        foreach ($rules['categories'] as $term) {
            if ($legacyNames->contains($term)) {
                return true;
            }
        }

        foreach (array_merge($rules['sports'], $rules['tag_terms']) as $term) {
            if ($this->containsPhrase($text, $term)) {
                return true;
            }
        }

        return false;
    }

    private function productSearchText(Product $product): string
    {
        return $this->normalizeText(implode(' ', [
            $product->name,
            $product->slug,
            $product->sku,
            $product->product_type,
            $product->brand,
            $product->short_description,
            strip_tags((string) $product->description_html),
            $this->flattenMixedValue($product->features),
            $this->flattenMixedValue($product->specifications),
            $this->flattenMixedValue($product->tags),
        ]));
    }

    private function upsertAssignment(int $productId, int $categoryId, bool $isPrimary, int $sortOrder, bool $allowPrimaryPromotion): bool
    {
        $existing = DB::table('category_product')
            ->where('category_id', $categoryId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            if ($isPrimary && $allowPrimaryPromotion && ! (bool) $existing->is_primary) {
                DB::table('category_product')
                    ->where('product_id', $productId)
                    ->update(['is_primary' => false, 'updated_at' => now()]);

                DB::table('category_product')
                    ->where('category_id', $categoryId)
                    ->where('product_id', $productId)
                    ->update(['is_primary' => true, 'updated_at' => now()]);
            }

            return false;
        }

        if ($isPrimary && $allowPrimaryPromotion) {
            DB::table('category_product')
                ->where('product_id', $productId)
                ->update(['is_primary' => false, 'updated_at' => now()]);
        }

        DB::table('category_product')->insert([
            'category_id' => $categoryId,
            'product_id' => $productId,
            'is_primary' => $isPrimary,
            'is_featured' => false,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /** @param array<string, int> $stats */
    private function ensureSinglePrimaryPerProduct(array &$stats): void
    {
        Product::withTrashed()
            ->select(['id', 'category_id', 'subcategory_id'])
            ->orderBy('id')
            ->chunkById(300, function ($products) use (&$stats): void {
                foreach ($products as $product) {
                    $assignments = DB::table('category_product')
                        ->where('product_id', $product->id)
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('category_id')
                        ->get();

                    if ($assignments->isEmpty()) {
                        continue;
                    }

                    $preferredId = (int) ($product->subcategory_id ?: $product->category_id ?: 0);
                    $primary = $preferredId > 0
                        ? $assignments->firstWhere('category_id', $preferredId)
                        : null;
                    $primary ??= $assignments->firstWhere('is_primary', 1) ?: $assignments->first();

                    $primaryId = (int) $primary->category_id;
                    $currentPrimaryCount = $assignments->where('is_primary', 1)->count();

                    if ($currentPrimaryCount !== 1 || ! (bool) $primary->is_primary) {
                        DB::table('category_product')
                            ->where('product_id', $product->id)
                            ->update(['is_primary' => false, 'updated_at' => now()]);

                        DB::table('category_product')
                            ->where('product_id', $product->id)
                            ->where('category_id', $primaryId)
                            ->update(['is_primary' => true, 'updated_at' => now()]);

                        $stats['primary_fixed']++;
                    }
                }
            });
    }

    /**
     * @param  Collection<int, int|null>  $parentById
     * @return array<int>
     */
    private function categoryWithAncestorIds(int $categoryId, Collection $parentById): array
    {
        $ids = [];
        $current = $categoryId;
        $visited = [];

        while ($current > 0 && ! isset($visited[$current])) {
            $visited[$current] = true;
            $ids[] = $current;
            $parent = $parentById->get($current);
            $current = $parent === null ? 0 : (int) $parent;
        }

        return $ids;
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Str::lower(Str::ascii($value));
        $value = str_replace(['&', '+'], ' and ', $value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: '';

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    private function containsPhrase(string $text, string $phrase): bool
    {
        if ($text === '' || $phrase === '') {
            return false;
        }

        return preg_match('/(?:^|\s)'.preg_quote($phrase, '/').'(?:\s|$)/u', $text) === 1;
    }

    private function flattenMixedValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value) || $value instanceof Collection) {
            return collect($value)
                ->flatMap(function ($item, $key): array {
                    return [$key, $this->flattenMixedValue($item)];
                })
                ->implode(' ');
        }

        return '';
    }

    private function flushCatalogCaches(): void
    {
        $this->treeService->rebuildClosure();
        $this->treeService->flushCache();
        $this->navigationService->flushCache();

        $facetVersion = (int) Cache::get('catalog.category-facets.version', 1);
        Cache::put('catalog.category-facets.version', $facetVersion + 1);
    }
}
