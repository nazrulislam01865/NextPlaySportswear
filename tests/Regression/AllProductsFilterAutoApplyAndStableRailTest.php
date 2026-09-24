<?php

$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/resources/css/storefront.css');
$component = file_get_contents($root.'/resources/views/components/storefront/product/category-filter-panel.blade.php');
$js = file_get_contents($root.'/resources/js/storefront.js');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

foreach (compact('css', 'component', 'js', 'controller', 'service') as $name => $contents) {
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
    preg_match('/\.storefront-clean-ui \.np-filter-shell--clean\s*\{[^}]*inline-size:\s*var\(--np-catalog-filter-width\)[^}]*min-inline-size:\s*var\(--np-catalog-filter-width\)[^}]*max-inline-size:\s*var\(--np-catalog-filter-width\)[^}]*contain:\s*inline-size/s', $css) === 1,
    'The desktop filter rail owns a fixed logical inline size and inline-size containment so expanded content cannot resize the grid rail.'
);

$expect(
    str_contains($component, 'data-product-filter-form'),
    'The reusable catalog filter form exposes a stable hook for automatic filtering.'
);

$expect(
    ! str_contains($component, '>Apply Filters</button>'),
    'The filter panel no longer requires an Apply Filters button.'
);

$expect(
    str_contains($js, "document.querySelectorAll('[data-product-filter-form]')")
        && str_contains($js, "form.addEventListener('change'")
        && str_contains($js, 'new FormData(form)')
        && str_contains($js, "url.searchParams.delete('page')")
        && str_contains($js, "'X-Storefront-Partial': 'product-results'"),
    'Checkbox/radio filter changes are serialized from the existing GET form and update only the product-results fragment.'
);

$expect(
    str_contains($js, "input[name=\"min_price\"], input[name=\"max_price\"]")
        && str_contains($js, 'setTimeout')
        && str_contains($js, 'clearTimeout'),
    'Price filters use a debounce instead of issuing a request for every keystroke.'
);

$expect(
    str_contains($js, 'syncCatalogSortInputs')
        && str_contains($js, "input[name=\"sort\"]"),
    'Automatic filtering keeps the current Sort By value synchronized with both desktop and mobile forms.'
);

$partialBranch = strpos($controller, "X-Storefront-Partial");
$filterOptionsCall = strpos($controller, '$filterOptions = $this->productCatalogService->filterOptions($filters);');
$expect(
    $partialBranch !== false && $filterOptionsCall !== false && $partialBranch < $filterOptionsCall,
    'Automatic filter requests still return before facet calculation, avoiding unnecessary filter-option queries.'
);

$expect(
    str_contains($service, '->with($this->listingRelations())'),
    'Automatic filter result hydration retains the existing eager-loaded listing relations and does not introduce N+1 card queries.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products auto-filter/stable-rail regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products auto-filter/stable-rail regression passed.\n";
