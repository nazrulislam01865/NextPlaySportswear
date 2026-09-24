<?php

$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/resources/css/storefront.css');
$component = file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php');
$shared = file_get_contents($root.'/resources/views/components/storefront/catalog/shared-filter-sections.blade.php');
$request = file_get_contents($root.'/app/Http/Requests/Storefront/ProductFilterRequest.php');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

foreach (compact('css', 'component', 'shared', 'request', 'service') as $name => $contents) {
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
    str_contains($css, '--np-catalog-filter-width: 336px;'),
    'Desktop catalog filter width is centralized in one reusable CSS variable.'
);

$expect(
    preg_match('/@media \(min-width: 1024px\).*?\.storefront-clean-ui \.np-product-layout\.has-filters\s*\{[^}]*grid-template-columns:\s*var\(--np-catalog-filter-width\)\s+minmax\(0,\s*1fr\)/s', $css) === 1,
    'The catalog grid reserves the final filter width from initial render.'
);

$expect(
    preg_match('/\.storefront-clean-ui \.np-filter-shell--clean\s*\{[^}]*width:\s*var\(--np-catalog-filter-width\)[^}]*min-width:\s*var\(--np-catalog-filter-width\)[^}]*max-width:\s*var\(--np-catalog-filter-width\)/s', $css) === 1,
    'The clean desktop filter shell has identical width, min-width, and max-width so expansion cannot resize it.'
);

$expect(
    preg_match('/\.storefront-clean-ui \.np-filter-shell--clean :where\([^}]*\.np-catalog-category-group[^}]*\)\s*\{[^}]*max-width:\s*100%/s', $css) === 1,
    'Expanded category content is constrained to the fixed filter rail instead of contributing intrinsic width.'
);

$expect(
    ! preg_match('/@media \(min-width: (?:1024|1280|1440)px\).*?grid-template-columns:\s*(?:250|300|318|324)px\s+minmax\(0,\s*1fr\)/s', $css),
    'Legacy competing desktop filter column widths are removed.'
);

$expect(
    str_contains($component, 'method="GET"')
        && str_contains($component, 'action="{{ route(\'products.index\') }}"')
        && str_contains($component, 'name="categories[]"')
        && str_contains($component, 'data-product-filter-form')
        && ! str_contains($component, '>Apply Filters</button>'),
    'Category filters remain real GET form controls and expose automatic filtering without an Apply Filters button.'
);

foreach (['sports[]', 'product_types[]', 'colors[]', 'materials[]', 'artwork_methods[]', 'moq[]', 'customization[]', 'availability[]', 'min_rating', 'min_price', 'max_price'] as $name) {
    $expect(str_contains($shared, 'name="'.$name.'"'), "Filter control {$name} remains wired in the shared filter form.");
}

foreach (['categories', 'sports', 'product_types', 'colors', 'materials', 'artwork_methods', 'attributes', 'min_price', 'max_price', 'moq', 'customization', 'availability', 'min_rating'] as $key) {
    $expect(str_contains($request, "'{$key}'"), "ProductFilterRequest still validates {$key}.");
}

$expect(
    str_contains($service, 'applyProductCategoryFilters($products, $filters[\'categories\'])')
        && str_contains($service, 'applyProductCategoryFilters($products, $filters[\'sports\'])')
        && str_contains($service, 'applyCommonCatalogFilters($products, $filters)')
        && str_contains($service, '->with($this->listingRelations())'),
    'Submitted filters still reach the existing optimized catalog query with eager-loaded listing relations.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products stable filter width/function regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products stable filter width/function regression passed.\n";
