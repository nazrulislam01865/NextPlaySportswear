<?php

$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/storefront/products/index.blade.php');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$js = file_get_contents($root.'/resources/js/storefront.js');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

foreach (compact('view', 'controller', 'js', 'service') as $name => $contents) {
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
    str_contains($view, 'data-product-results')
        && str_contains($view, "@include('storefront.products._results'"),
    'The All Products page renders product cards and pagination through one replaceable results region.'
);

$expect(
    ! str_contains($view, 'window.location.assign') && ! str_contains($view, 'onchange='),
    'Sort By no longer performs full-page navigation from inline handlers.'
);

$partialBranch = strpos($controller, "X-Storefront-Partial");
$filterOptionsCall = strpos($controller, '$filterOptions = $this->productCatalogService->filterOptions($filters);');
$expect(
    $partialBranch !== false && $filterOptionsCall !== false && $partialBranch < $filterOptionsCall,
    'Partial product-result requests return before filter facet calculation, avoiding unnecessary catalog/facet queries.'
);

$expect(
    str_contains($controller, "view('storefront.products._results'")
        && str_contains($controller, "'products' => \$products"),
    'The controller returns only the product results partial for asynchronous sorting.'
);

$expect(
    str_contains($js, 'const setupProductCatalogPartialUpdates')
        && str_contains($js, "'X-Storefront-Partial': 'product-results'")
        && str_contains($js, 'new AbortController()')
        && str_contains($js, "history.pushState")
        && str_contains($js, 'results.innerHTML = html')
        && str_contains($js, 'setupProductCardWishlists()'),
    'Sort By fetches only the results fragment, cancels stale requests, updates the URL without reload, and rebinds product-card behavior.'
);

$expect(
    str_contains($js, 'let productActivityTimer = null;')
        && str_contains($js, 'let productActivityPing = null;')
        && str_contains($js, 'if (productActivityTimer !== null)')
        && str_contains($js, "document.querySelectorAll('[data-product-card][data-product-id]')"),
    'Product activity refresh remains singleton/idempotent while discovering dynamically replaced cards, preventing duplicate timers.'
);


$expect(
    str_contains($service, '->with($this->listingRelations())')
        && str_contains($service, "'images'")
        && str_contains($service, "'priceTiers'")
        && str_contains($service, "'optionGroups'"),
    'Product result hydration keeps required card relations eager-loaded so partial sorting does not introduce per-product relation queries.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products partial sort update regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products partial sort update regression passed.\n";
