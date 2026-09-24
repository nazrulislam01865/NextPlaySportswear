<?php

$root = dirname(__DIR__, 2);
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');

if ($service === false) {
    fwrite(STDERR, "Unable to read ProductCatalogService source.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$searchStart = strpos($service, 'private function applyProductSearchFilters');
$searchEnd = strpos($service, 'private function applyProductListingSort', $searchStart ?: 0);
$searchMethod = ($searchStart !== false && $searchEnd !== false)
    ? substr($service, $searchStart, $searchEnd - $searchStart)
    : '';

foreach (["products.name", "products.sku", "products.brand", "products.product_type"] as $needle) {
    $expect(str_contains($searchMethod, $needle), "Product search still matches {$needle}.");
}

$queryBlockStart = strpos($searchMethod, 'if (filled($query))');
$queryBlock = $queryBlockStart !== false ? substr($searchMethod, $queryBlockStart) : '';

foreach (["orWhereHas('category'", "orWhereHas('subcategory'", "orWhereHas('categories'"] as $needle) {
    $expect(! str_contains($queryBlock, $needle), "Free-text product search must not match through {$needle}.");
}

$fallbackStart = strpos($service, 'private function filteredFallbackProducts');
$fallbackEnd = strpos($service, 'public function categoryFilterTree', $fallbackStart ?: 0);
$fallbackMethod = ($fallbackStart !== false && $fallbackEnd !== false)
    ? substr($service, $fallbackStart, $fallbackEnd - $fallbackStart)
    : '';

foreach (["product['category']", "product['subcategory']", "product['sport']"] as $needle) {
    $searchLine = 'Str::contains(Str::lower($'.$needle;
    $expect(! str_contains($fallbackMethod, $searchLine), "Fallback free-text search must not match {$needle}.");
}

if ($failures !== []) {
    fwrite(STDERR, "Strict product search relevance regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Strict product search relevance regression passed.\n";
