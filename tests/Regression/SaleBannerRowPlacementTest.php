<?php

$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$salePage = file_get_contents($root.'/resources/views/storefront/products/sale.blade.php');
$saleResults = file_get_contents($root.'/resources/views/storefront/products/_sale-results.blade.php');
$storefrontCss = file_get_contents($root.'/resources/css/storefront.css');

foreach (compact('controller', 'salePage', 'saleResults', 'storefrontCss') as $name => $contents) {
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

$saleStart = strpos($controller, 'public function sale');
$helpersStart = strpos($controller, 'private function insertionIndices', $saleStart ?: 0);
$saleBlock = ($saleStart !== false && $helpersStart !== false)
    ? substr($controller, $saleStart, $helpersStart - $saleStart)
    : '';

$expect(str_contains($saleBlock, 'PromotionBannerPlacement::SALE_TOP'), 'Sale controller resolves the shared top slot.');
$expect(str_contains($saleBlock, 'PromotionBannerPlacement::SALE_MIDDLE'), 'Sale controller resolves the shared middle slot.');
$expect(substr_count($saleBlock, '$this->saleBanners->resolvePageSlot(') === 2, 'Sale controller resolves exactly the top and middle page slots.');
$expect(str_contains($saleBlock, '$saleMiddleInsertionIndices = $this->insertionIndices($saleProducts->count());'), 'Sale controller computes one midpoint map from the unique products on the current page.');
$expect(! str_contains($saleBlock, "'top_banner'") && ! str_contains($saleBlock, "'after_row_banner'"), 'Campaign sections no longer own banner payloads.');

$topBannerPosition = strpos($salePage, '<x-storefront.sale-banner :banner="$saleTopBanner"');
$activeBarPosition = strpos($salePage, 'np-catalog-active-bar');
$expect(
    $topBannerPosition !== false && $activeBarPosition !== false && $topBannerPosition < $activeBarPosition,
    'The page-level Sale top banner renders before the summary/sort section.'
);

$expect(str_contains($saleResults, ':banner="$saleMiddleBanner"'), 'Sale results render only the page-level middle payload.');
$expect(! str_contains($saleResults, 'after_row_banner') && ! str_contains($saleResults, 'top_banner'), 'Legacy per-section banner variables are absent.');
foreach ([2, 4, 6, 8, 10] as $interval) {
    $expect(! str_contains($saleResults, '% '.$interval), "Legacy repeated {$interval}-item interval logic is absent.");
}
foreach ([1, 2, 3, 4, 5] as $columns) {
    $expect(str_contains($saleResults, 'np-promotion-midpoint-banner--cols-{{ $columns }}'), 'Sale results use the shared breakpoint-specific midpoint wrapper.');
}

$expect(str_contains($saleResults, 'np-sale-products-grid'), 'Sale product grid retains its Sale-specific class.');
$expect(
    preg_match('/\.storefront-clean-ui\s+\.np-sale-page\s+\.np-sale-products-grid\s*\{[^}]*grid-auto-rows:\s*auto\s*!important;/s', $storefrontCss) === 1,
    'Sale product rows keep natural row sizing.'
);
$expect(
    preg_match('/\.storefront-clean-ui\s+\.np-sale-page\s+\.np-store-sale-banner\s*\{[^}]*height:\s*110px;[^}]*max-height:\s*110px/s', $storefrontCss) === 1,
    'Sale banners retain the compact 110px styling.'
);
foreach ([1, 2, 3, 4, 5] as $columns) {
    $expect(str_contains($storefrontCss, '.np-promotion-midpoint-banner--cols-'.$columns), "Responsive midpoint visibility exists for {$columns} columns.");
}

if ($failures !== []) {
    fwrite(STDERR, "Sale banner row placement regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Sale banner row placement regression passed.\n";
