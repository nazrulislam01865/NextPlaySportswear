<?php

/** Run directly: php tests/Regression/NextPlayCatalogFocusedFixesTest.php */
$root = dirname(__DIR__, 2);
$productForm = (string) file_get_contents($root.'/resources/views/admin/products/_form.blade.php');
$categories = (string) file_get_contents($root.'/resources/views/admin/categories/index.blade.php');
$css = (string) file_get_contents($root.'/public/css/nextplay-catalog-focused-fixes-20261113.css');

$checks = [
    'product highlight text is still submitted' => str_contains($productForm, ':name="`features[${index}]`"'),
    'highlight icon upload remains available' => str_contains($productForm, ':name="`feature_icon_files[${index}]`"'),
    'highlight visibility remains controlled by the existing switch' => str_contains($productForm, 'normalizeFeatureStorefront()'),
    'storefront still limits visible highlights to four' => str_contains($productForm, 'storefrontHighlightCount() >= 4'),
    'mobile highlight fields have visible labels' => str_contains($productForm, 'np-product-highlight-mobile-label'),
    'only standard pricing mode is submitted' => str_contains($productForm, 'name="pricing_mode" value="standard"'),
    'bulk pricing toggle has been removed' => ! str_contains($productForm, 'Bulk pricing') && ! str_contains($productForm, 'np-price-mode mt-4'),
    'quantity tiers remain available' => str_contains($productForm, 'addPriceRow()') && str_contains($productForm, 'addPriceHeader()'),
    'Excel import remains available' => str_contains($productForm, 'Import Excel'),
    'category actions reuse the shared button classes' => str_contains($categories, 'category-header-action-primary np-primary-button') && str_contains($categories, 'category-header-action-light np-secondary-button'),
    'category filter uses shared primary button' => str_contains($categories, 'category-filter-button np-primary-button'),
    'category import and bulk buttons use shared primary button' => str_contains($categories, 'category-import-button--compact np-primary-button') && str_contains($categories, 'category-bulk-button--compact np-primary-button'),
    'both target pages load the scoped stylesheet' => str_contains($productForm, 'nextplay-catalog-focused-fixes-20261113.css') && str_contains($categories, 'nextplay-catalog-focused-fixes-20261113.css'),
    'styling uses existing Catalog tokens' => str_contains($css, 'var(--np-catalog-control-height') && str_contains($css, 'var(--np-catalog-font)'),
    'responsive highlight layout avoids fixed width columns' => str_contains($css, 'container-type: inline-size') && str_contains($css, '@container (max-width: 940px)'),
];

$failed = 0;
foreach ($checks as $label => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (! $passed) {
        ++$failed;
    }
}
exit($failed === 0 ? 0 : 1);
