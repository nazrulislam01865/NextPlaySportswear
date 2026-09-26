<?php

$root = dirname(__DIR__, 2);

$request = file_get_contents($root.'/app/Http/Requests/Storefront/ProductFilterRequest.php');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$shared = file_get_contents($root.'/resources/views/components/storefront/catalog/shared-filter-sections.blade.php');
$productPanel = file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php');
$categoryPanel = file_get_contents($root.'/resources/views/components/storefront/category/filter-panel.blade.php');
$genderController = file_get_contents($root.'/app/Http/Controllers/Admin/GenderController.php');

foreach (compact('request', 'controller', 'service', 'shared', 'productPanel', 'categoryPanel', 'genderController') as $name => $contents) {
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

$expect(
    str_contains($request, "'genders' => ['nullable', 'array'")
        && str_contains($request, "'genders.*'")
        && str_contains($request, "'genders' => \$this->integerArray(\$validated['genders'] ?? [])"),
    'All Products accepts gender ids and normalizes them through the existing ProductFilterRequest.'
);

$expect(
    str_contains($controller, "'genders'") && str_contains($controller, "foreach (['categories', 'sports', 'product_types', 'genders'"),
    'Selected genders count toward the existing All Products active-filter total.'
);

$expect(
    str_contains($service, 'use App\\Models\\Gender;')
        && str_contains($service, "collect(\$filters['genders'] ?? [])")
        && str_contains($service, "whereIn('products.gender_id', \$genderIds)"),
    'The catalog query filters products by selected gender master-data ids.'
);

$expect(
    str_contains($service, "'products.gender_id'")
        && str_contains($service, 'Gender::query()')
        && str_contains($service, '->active()')
        && str_contains($service, '->ordered()')
        && str_contains($service, "'genders' => \$genders"),
    'Gender filter options are sourced from active, ordered Gender master data and included in common facet options.'
);

$genderBlockStart = strpos($service, '$genders = $includeGender ? Gender::query()');
$genderBlockEnd = $genderBlockStart === false ? false : strpos($service, '[$colors, $materials]', $genderBlockStart);
$genderBlock = ($genderBlockStart !== false && $genderBlockEnd !== false)
    ? substr($service, $genderBlockStart, $genderBlockEnd - $genderBlockStart)
    : '';

$expect(
    $genderBlock !== ''
        && ! str_contains($genderBlock, '->whereIn(\'id\', $genderCounts->keys()->all())')
        && ! str_contains($genderBlock, '->filter(fn (array $option): bool => $option[\'count\'] > 0)'),
    'All active Gender master-data values remain visible in All Products even when no current product is assigned to a value.'
);

$expect(
    str_contains($shared, "'showGender' => false")
        && str_contains($shared, "(\$showGender && (\$options['genders'] ?? []) !== [])")
        && str_contains($shared, 'name="genders[]"')
        && str_contains($shared, '<span>Gender</span>'),
    'The reusable filter sections expose a gated Gender checkbox section.'
);

$expect(
    str_contains($productPanel, ':show-gender="true"'),
    'The All Products filter panel explicitly enables the Gender section.'
);

$expect(
    ! str_contains($categoryPanel, ':show-gender="true"') && ! str_contains($categoryPanel, 'showGender'),
    'Category-page filters are left unchanged and do not enable the All Products Gender section.'
);


$expect(
    str_contains($service, 'commonFilterOptions($facetScoped, includeGender: true)')
        && str_contains($service, 'public function commonFilterOptions(Builder $baseQuery, bool $includeGender = false): array')
        && str_contains($service, '$includeGender ? Gender::query()'),
    'Gender facet loading is explicitly enabled only for All Products instead of adding unnecessary category-page queries.'
);

$expect(
    str_contains($genderController, 'ProductCatalogCacheService')
        && substr_count($genderController, '$this->productCatalogCache->flush();') >= 3,
    'Gender master-data create, update, and delete invalidate cached storefront filter options.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products gender/master-data regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products gender/master-data regression passed.\n";
