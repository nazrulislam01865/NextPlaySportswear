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

$expect(
    str_contains($service, "'price-low' => \$this->applyListingPriceSort(\$products, 'asc')")
        && str_contains($service, "'price-high' => \$this->applyListingPriceSort(\$products, 'desc')"),
    'Both visible price sort directions must use the shared listing price sorter.'
);

$expect(
    str_contains($service, 'ppt.compare_at_price'),
    'The listing price expression must include the active quantity-tier discount price shown on product cards.'
);

$expect(
    str_contains($service, 'products.compare_at_price'),
    'The listing price expression must include the product-level discount price shown on product cards.'
);

$expect(
    str_contains($service, ".' AND '.\$tierDiscount.' < '.\$originalPrice")
        && str_contains($service, ".' THEN '.\$tierDiscount")
        && str_contains($service, ".' WHEN '.\$productDiscount.' IS NOT NULL'")
        && str_contains($service, ".' AND '.\$productDiscount.' < '.\$originalPrice")
        && str_contains($service, ".' THEN '.\$productDiscount")
        && str_contains($service, ".' ELSE '.\$originalPrice"),
    'Price sorting must use the same valid-discount precedence as productCardPricing instead of the undiscounted tier/base price.'
);

if ($failures !== []) {
    fwrite(STDERR, "All Products visible-price sort regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products visible-price sort regression passed.\n";
