<?php

$root = dirname(__DIR__, 2);
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$component = file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php');
$js = file_get_contents($root.'/resources/js/storefront.js');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');

foreach (compact('service', 'component', 'js', 'controller') as $name => $contents) {
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$name} source.\n");
        exit(1);
    }
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$treeStart = strpos($service, 'public function categoryFilterTree');
$treeEnd = strpos($service, 'public function normalizeCategoryFilterIds', $treeStart ?: 0);
$treeMethod = ($treeStart !== false && $treeEnd !== false) ? substr($service, $treeStart, $treeEnd - $treeStart) : '';

$expect(
    str_contains($treeMethod, 'categoryProductCounts('),
    'Category filter tree uses one batched category-product count map instead of per-category count queries.'
);

$expect(
    ! str_contains($treeMethod, 'countProductsForCategoryFilter('),
    'Category filter tree does not execute one product count query per parent/child category.'
);

$expect(
    str_contains($treeMethod, "'count' => count(\$children)"),
    'A top-level category badge shows the number of visible child categories, not its product count.'
);

$expect(
    str_contains($service, 'private function categoryProductCounts(array $categoryIds): array')
        && str_contains($service, 'private function expandedCategoryFilterMap(array $categoryIds): array'),
    'Subcategory counts are calculated through batched descendant-aware helpers.'
);

$expect(
    str_contains($service, "DB::table('category_product as category_count_cp')")
        && str_contains($service, "joinSub(")
        && str_contains($service, "whereIn('category_count_cp.category_id'"),
    'Pivot-based product assignments are counted in one batched query against published products.'
);

$expect(
    str_contains($service, "->whereIn('products.category_id', \$relevantCategoryIds)")
        && str_contains($service, "->orWhereIn('products.subcategory_id', \$relevantCategoryIds)"),
    'Legacy primary/subcategory assignments are counted in one batched published-product query.'
);

$expect(
    str_contains($service, 'array_unique($productIds)') || str_contains($service, '->unique()'),
    'Subcategory product counts are distinct so duplicate assignment paths cannot inflate counts.'
);

$expect(
    str_contains($service, 'applyProductCategoryFilters($products, $filters[\'categories\'])')
        && str_contains($service, 'applyProductCategoryFilters($products, $filters[\'sports\'])')
        && str_contains($service, 'applyCommonCatalogFilters($products, $filters)')
        && str_contains($service, 'whereProductsMatchAnyCategory($products, $filterCategoryIds)'),
    'Selected category and shared filters still reach the central optimized product query.'
);

$expect(
    str_contains($service, "whereIn('products.category_id', \$categoryIds)")
        && str_contains($service, "orWhereIn('products.subcategory_id', \$categoryIds)")
        && str_contains($service, "orWhereHas('categories'"),
    'Category filtering matches all supported assignment paths: category_id, subcategory_id, and category_product.'
);

$expect(
    str_contains($component, 'name="categories[]"')
        && str_contains($js, "form.addEventListener('change'")
        && str_contains($js, "'X-Storefront-Partial': 'product-results'")
        && str_contains($controller, "X-Storefront-Partial"),
    'Clicking a category checkbox still applies the filter immediately through the existing partial-results request.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products category count/filter correctness regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products category count/filter correctness regression passed.\n";
