<?php

/* Standalone layout regression: php tests/Regression/CatalogFilterNaturalExpansionTest.php */

$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/resources/css/storefront.css');
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$builtPath = $root.'/public/build/'.($manifest['resources/css/storefront.css']['file'] ?? '');
$builtCss = is_file($builtPath) ? file_get_contents($builtPath) : '';
$components = [
    'product' => file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php'),
    'category' => file_get_contents($root.'/resources/views/components/storefront/category/filter-panel.blade.php'),
    'shared' => file_get_contents($root.'/resources/views/components/storefront/catalog/shared-filter-sections.blade.php'),
];
$pages = [
    'All Products' => file_get_contents($root.'/resources/views/storefront/products/index.blade.php'),
    'Sale' => file_get_contents($root.'/resources/views/storefront/products/sale.blade.php'),
    'Category' => file_get_contents($root.'/resources/views/storefront/categories/show.blade.php'),
];
$errors = [];
$check = static function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) {
        $errors[] = $message;
    }
};
$check(preg_match('/\.storefront-clean-ui \.np-filter-shell--clean\.np-filter-rail--compact,[^}]*position:\s*sticky;[^}]*top:\s*var\(--np-catalog-filter-sticky-top,\s*8rem\);/s', $css) === 1,
    'Desktop filter must remain sticky in both compact and expanded states.');
$check(preg_match('/\.storefront-clean-ui \.np-filter-shell--clean \.np-catalog-filter-form\s*\{[^}]*height:\s*auto;[^}]*overflow:\s*visible;/s', $css) === 1,
    'Desktop filter form must not clip expanded content.');
$check(preg_match('/\.storefront-clean-ui \.np-filter-shell--clean \.np-catalog-filter-scroll\s*\{[^}]*overflow:\s*visible;/s', $css) === 1,
    'Desktop options must not add nested scrollbars.');
$check(! str_contains($css, '.np-catalog-option-list--scroll'),
    'Desktop category and option groups must not be capped by a scrollbar rule.');
$check(preg_match('/@media \(max-width: 1023px\).*?\.storefront-clean-ui \.np-filter-drawer \.np-catalog-filter-form\s*\{[^}]*height:\s*100%/s', $css) === 1 && str_contains($css, '.np-catalog-filter-scroll'),
    'Mobile drawer must retain its viewport-safe outer scrolling behavior.');
foreach ($components as $name => $template) {
    $check($template !== false && ! str_contains($template, 'np-catalog-option-list--scroll') && ! str_contains($template, 'filterScrollThreshold'),
        "{$name} component must render every expanded option without a nested scrollbar.");
}
foreach ($pages as $name => $template) {
    $check($template !== false && str_contains($template, 'np-filter-shell--clean') && str_contains($template, 'np-filter-drawer'),
        "{$name} page must use the shared desktop filters and mobile drawer.");
}
$check($builtCss !== '' && str_contains($builtCss, 'NEXTPLAY_FILTER_DIRECTIONAL_FLOATING_V5'),
    'Shipped Vite CSS must include the directional floating and natural-height rules, not just source CSS.');

if ($errors !== []) {
    fwrite(STDERR, "Catalog natural expansion regression failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}
echo "Catalog natural expansion regression passed.\n";
