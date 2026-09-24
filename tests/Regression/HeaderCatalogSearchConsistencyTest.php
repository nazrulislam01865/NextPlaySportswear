<?php

$root = dirname(__DIR__, 2);
$header = file_get_contents($root.'/resources/views/components/storefront/header.blade.php');
$products = file_get_contents($root.'/resources/views/storefront/products/index.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

foreach (compact('header', 'products', 'css', 'service') as $name => $contents) {
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
    str_contains($header, 'class="np-header-search np-catalog-search-shell"')
        && str_contains($header, 'class="np-header-search-input np-catalog-search-input"')
        && str_contains($header, 'class="np-header-search-submit np-catalog-search-submit"'),
    'Desktop header search reuses the same catalog search shell/input/button styling contract.'
);

$expect(
    str_contains($products, 'np-catalog-search-shell')
        && str_contains($products, 'np-catalog-search-input')
        && str_contains($products, 'np-catalog-search-submit'),
    'All Products hero search uses the same reusable search styling contract as the header.'
);

$expect(
    preg_match('/\.np-header-search\s*\{[^}]*max-width:\s*500px/s', $css) === 1,
    'Header search keeps the existing 500px desktop width cap.'
);

$expect(
    str_contains($css, '.np-catalog-search-shell')
        && str_contains($css, '.np-catalog-search-input')
        && str_contains($css, '.np-catalog-search-submit'),
    'Shared catalog-search CSS owns the visual design instead of duplicating header-only styling.'
);

$searchStart = strpos($service, 'private function applyProductSearchFilters');
$searchEnd = strpos($service, 'private function applyProductListingSort', $searchStart ?: 0);
$searchMethod = ($searchStart !== false && $searchEnd !== false) ? substr($service, $searchStart, $searchEnd - $searchStart) : '';

foreach (["products.name", "products.sku", "products.brand", "products.product_type", "orWhereHas('category'", "orWhereHas('subcategory'", "orWhereHas('categories'"] as $needle) {
    $expect(str_contains($searchMethod, $needle), "Catalog search matches {$needle}.");
}

$queryBlockStart = strpos($searchMethod, 'if (filled($query))');
$queryBlock = $queryBlockStart !== false ? substr($searchMethod, $queryBlockStart) : '';
$expect(
    ! str_contains($queryBlock, 'products.short_description') && ! str_contains($queryBlock, "products.tags', 'like'"),
    'Free-text catalog search does not match broad description/tag text that can surface unrelated products.'
);

$fallbackStart = strpos($service, 'private function filteredFallbackProducts');
$fallbackEnd = strpos($service, 'public function categoryFilterTree', $fallbackStart ?: 0);
$fallbackMethod = ($fallbackStart !== false && $fallbackEnd !== false) ? substr($service, $fallbackStart, $fallbackEnd - $fallbackStart) : '';

$expect(
    str_contains($fallbackMethod, "product['brand']")
        && str_contains($fallbackMethod, "product['product_type']")
        && ! str_contains($fallbackMethod, "implode(' ', \$product['tags']"),
    'Fallback catalog search follows the same focused matching semantics as database-backed search.'
);

if ($failures !== []) {
    fwrite(STDERR, "Header/catalog search consistency regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Header/catalog search consistency regression passed.\n";
