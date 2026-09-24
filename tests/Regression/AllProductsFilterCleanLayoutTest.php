<?php

$root = dirname(__DIR__, 2);
$component = file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php');
$view = file_get_contents($root.'/resources/views/storefront/products/index.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

foreach (compact('component', 'view', 'css', 'service') as $name => $contents) {
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
    ! str_contains($component, 'Product finder')
        && ! str_contains($component, 'Choose the options that match your order.')
        && ! str_contains($view, 'Choose a category, then refine by the product details that matter to your order.')
        && ! str_contains($view, 'Choose options, then apply to update the products.')
        && stripos($view, 'product finder') === false,
    'The All Products filter removes the Product finder eyebrow and descriptive helper copy.'
);

$expect(
    str_contains($component, 'placeholder="Search"')
        && ! str_contains($component, 'placeholder="Search categories"'),
    'The category search field uses the concise "Search" placeholder.'
);

$expect(
    str_contains($view, 'np-filter-shell--clean'),
    'The desktop All Products sidebar opts into the clean filter-shell presentation without affecting the mobile drawer.'
);

$expect(
    preg_match('/@media \(min-width: 1024px\).*?\.storefront-clean-ui \.np-filter-shell--clean\s*\{[^}]*height:\s*auto[^}]*overflow:\s*visible[^}]*position:\s*static[^}]*border:\s*0\s*!important[^}]*background:\s*transparent[^}]*box-shadow:\s*none\s*!important/s', $css) === 1,
    'The desktop clean filter shell has no fixed height, clipping, sticky positioning, border, background card, or shadow.'
);

$expect(
    preg_match('/\.storefront-clean-ui \.np-filter-shell--clean \.np-catalog-filter-scroll\s*\{[^}]*overflow:\s*visible[^}]*padding:/s', $css) === 1,
    'The desktop filter options expand naturally without an internal scrollbar.'
);

$expect(
    preg_match('/\.storefront-clean-ui \.np-filter-shell--clean \.np-catalog-filter-actions\s*\{[^}]*background:\s*transparent[^}]*box-shadow:\s*none/s', $css) === 1,
    'The desktop Clear/Apply area is visually integrated instead of using a boxed/sticky background.'
);

$expect(
    preg_match('/@media \(max-width: 1023px\).*?\.storefront-clean-ui \.np-filter-drawer \.np-catalog-filter-form\s*\{[^}]*height:\s*100%/s', $css) === 1
        && preg_match('/\.storefront-clean-ui \.np-catalog-filter-scroll\s*\{[^}]*overflow-y:\s*auto/s', $css) === 1,
    'The mobile drawer keeps viewport-safe scrolling so lower filter controls remain reachable.'
);

$expect(
    str_contains($component, 'method="GET"')
        && str_contains($component, 'name="sort"')
        && str_contains($component, 'data-product-filter-form')
        && ! str_contains($component, '>Apply Filters</button>')
        && str_contains($service, '->with($this->listingRelations())'),
    'Existing GET filter semantics, active sorting, automatic filter hook, and eager-loaded product card relations remain intact.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products clean filter layout regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products clean filter layout regression passed.\n";
