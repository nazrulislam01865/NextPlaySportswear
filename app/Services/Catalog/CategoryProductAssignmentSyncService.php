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
     * Rebuild storefront category assignments from trusted product fields and
     * explicit safe category rules.
     *
     * The rebuild is deliberately batch-oriented: categories are loaded once,
     * products are scanned in chunks, existing assignments are fetched once per
     * chunk when requested, pivot rows are bulk-upserted, and legacy product
     * category fields are synchronized with one CASE update per chunk. This keeps
     * query growth bounded by product chunks rather than by product/category rows.
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

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id', 'name', 'menu_label', 'slug', 'category_type', 'match_rules']);

        if ($categories->isEmpty()) {
            return $stats;
        }

        $categoriesById = $categories->keyBy(fn (Category $category): int => (int) $category->id);
        $parentById = $categories->mapWithKeys(fn (Category $category): array => [
            (int) $category->id => $category->parent_id === null ? null : (int) $category->parent_id,
        ]);
        $parentIds = $categories->pluck('parent_id')
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->flip();
        $leafCategoryIds = $categories
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $parentIds->has($id))
            ->flip();
        $ruleCategories = collect($this->trustedRuleCategoryMap($categories))
            ->filter(fn (array $rules, int $categoryId): bool => $leafCategoryIds->has($categoryId))
            ->all();

        DB::transaction(function () use (
            &$stats,
            $resetExisting,
            $categoriesById,
            $parentById,
            $leafCategoryIds,
            $ruleCategories,
        ): void {
            if ($resetExisting) {
                $stats['assignments_deleted'] = (int) DB::table('category_product')->count();
                DB::table('category_product')->delete();
            } else {
                $leafIds = $leafCategoryIds->keys()->all();
                $deleteQuery = DB::table('category_product');
                if ($leafIds !== []) {
                    $deleteQuery->whereNotIn('category_id', $leafIds);
                }
                $stats['assignments_deleted'] = (int) $deleteQuery->delete();
            }

            Product::withTrashed()
                ->select([
                    'id', 'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'product_type', 'brand',
                    'short_description', 'description_html', 'features', 'specifications', 'tags', 'sort_order',
                ])
                ->orderBy('id')
                ->chunkById(300, function (Collection $products) use (
                    &$stats,
                    $resetExisting,
                    $categoriesById,
                    $parentById,
                    $leafCategoryIds,
                    $ruleCategories,
                ): void {
                    $productIds = $products->pluck('id')->map(fn ($id): int => (int) $id)->all();
                    $existingByProduct = $resetExisting
                        ? collect()
                        : DB::table('category_product')
                            ->whereIn('product_id', $productIds)
                            ->get(['product_id', 'category_id', 'is_primary', 'is_featured', 'sort_order'])
                            ->groupBy(fn ($row): int => (int) $row->product_id);

                    $assignmentRows = [];
                    $legacyUpdates = [];
                    $now = now();

                    foreach ($products as $product) {
                        $productId = (int) $product->id;
                        $stats['products_scanned']++;
                        $stats['trusted_rule_products_scanned']++;

                        $rawLegacyIds = collect([$product->category_id, $product->subcategory_id])
                            ->filter(fn ($id): bool => $id !== null && (int) $id > 0)
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values();

                        if ($rawLegacyIds->isEmpty()) {
                            $stats['products_without_category']++;
                        } else {
                            $stats['products_with_legacy_category']++;
                        }

                        $legacyLeafIds = $rawLegacyIds
                            ->filter(fn (int $categoryId): bool => $leafCategoryIds->has($categoryId))
                            ->values();
                        $stats['invalid_category_references'] += $rawLegacyIds->count() - $legacyLeafIds->count();

                        $legacyNames = $this->legacyCategoryNamesForProduct($product, $categoriesById, $parentById);
                        $text = $this->productSearchText($product);
                        $matchedRuleIds = collect();

                        foreach ($ruleCategories as $categoryId => $rules) {
                            if ($this->rulesMatchProduct($rules, $text, $legacyNames)) {
                                $matchedRuleIds->push((int) $categoryId);
                            }
                        }

                        $matchedRuleIds = $matchedRuleIds->unique()->values();
                        if ($matchedRuleIds->isNotEmpty()) {
                            $stats['trusted_rule_products_matched']++;
                        }

                        $existingRows = collect($existingByProduct->get($productId, []))
                            ->filter(fn ($row): bool => $leafCategoryIds->has((int) $row->category_id));
                        $existingIds = $existingRows
                            ->pluck('category_id')
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values();

                        $assignmentIds = ($resetExisting ? collect() : $existingIds)
                            ->merge($legacyLeafIds)
                            ->merge($matchedRuleIds)
                            ->filter(fn (int $categoryId): bool => $leafCategoryIds->has($categoryId))
                            ->unique()
                            ->sort()
                            ->values();

                        if ($assignmentIds->isEmpty()) {
                            $legacyUpdates[] = [
                                'id' => $productId,
                                'category_id' => null,
                                'subcategory_id' => null,
                            ];
                            continue;
                        }

                        $preferredId = (int) ($product->subcategory_id ?: $product->category_id ?: 0);
                        if (! $assignmentIds->contains($preferredId)) {
                            $existingPrimary = $existingRows->first(fn ($row): bool => (bool) $row->is_primary);
                            $preferredId = $existingPrimary ? (int) $existingPrimary->category_id : 0;
                        }
                        if (! $assignmentIds->contains($preferredId)) {
                            $preferredId = (int) ($legacyLeafIds->first() ?: $assignmentIds->first());
                        }

                        $existingPrimaryIds = $existingRows
                            ->filter(fn ($row): bool => (bool) $row->is_primary)
                            ->pluck('category_id')
                            ->map(fn ($id): int => (int) $id)
                            ->values();
                        if ($existingPrimaryIds->count() !== 1 || (int) $existingPrimaryIds->first() !== $preferredId) {
                            $stats['primary_fixed']++;
                        }

                        $existingMap = $existingRows->keyBy(fn ($row): int => (int) $row->category_id);
                        foreach ($assignmentIds as $categoryId) {
                            $existing = $existingMap->get($categoryId);
                            $isLegacy = $legacyLeafIds->contains($categoryId);

                            if ($existing) {
                                $stats['assignments_existing']++;
                            } elseif ($isLegacy) {
                                $stats['legacy_assignments_created']++;
                            } else {
                                $stats['trusted_rule_assignments_created']++;
                            }

                            $assignmentRows[] = [
                                'category_id' => (int) $categoryId,
                                'product_id' => $productId,
                                'is_primary' => (int) $categoryId === $preferredId,
                                'is_featured' => $existing ? (bool) $existing->is_featured : false,
                                'sort_order' => $existing ? (int) $existing->sort_order : (int) ($product->sort_order ?? 0),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        $rootId = $this->rootCategoryId($preferredId, $parentById);
                        $legacyUpdates[] = [
                            'id' => $productId,
                            'category_id' => $rootId,
                            'subcategory_id' => $rootId === $preferredId ? null : $preferredId,
                        ];
                    }

                    foreach (array_chunk($assignmentRows, 1000) as $rows) {
                        DB::table('category_product')->upsert(
                            $rows,
                            ['category_id', 'product_id'],
                            ['is_primary', 'is_featured', 'sort_order', 'updated_at']
                        );
                    }

                    $this->bulkUpdateProductLegacyCategories($legacyUpdates);
                });
        });

        $this->flushCatalogCaches();

        return $stats;
    }

    /**
     * Synchronize legacy category_id/subcategory_id values with a single set-based
     * update for the product chunk. This avoids one UPDATE per product.
     *
     * @param array<int, array{id:int,category_id:?int,subcategory_id:?int}> $updates
     */
    private function bulkUpdateProductLegacyCategories(array $updates): void
    {
        if ($updates === []) {
            return;
        }

        $categoryCases = [];
        $subcategoryCases = [];
        $categoryBindings = [];
        $subcategoryBindings = [];
        $ids = [];

        foreach ($updates as $update) {
            $id = (int) $update['id'];
            $ids[] = $id;
            $categoryCases[] = 'WHEN ? THEN ?';
            $categoryBindings[] = $id;
            $categoryBindings[] = $update['category_id'];
            $subcategoryCases[] = 'WHEN ? THEN ?';
            $subcategoryBindings[] = $id;
            $subcategoryBindings[] = $update['subcategory_id'];
        }

        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE products SET '
            .'category_id = CASE id '.implode(' ', $categoryCases).' ELSE category_id END, '
            .'subcategory_id = CASE id '.implode(' ', $subcategoryCases).' ELSE subcategory_id END, '
            .'updated_at = ? '
            .'WHERE id IN ('.$idPlaceholders.')';

        DB::update($sql, [
            ...$categoryBindings,
            ...$subcategoryBindings,
            now(),
            ...$ids,
        ]);
    }

    /** @param Collection<int, int|null> $parentById */
    private function rootCategoryId(int $categoryId, Collection $parentById): int
    {
        $current = $categoryId;
        $visited = [];

        while ($current > 0 && ! isset($visited[$current])) {
            $visited[$current] = true;
            $parent = $parentById->get($current);
            if ($parent === null) {
                return $current;
            }
            $current = (int) $parent;
        }

        return $categoryId;
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

    /**
     * @param Collection<int, int|null> $parentById
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
