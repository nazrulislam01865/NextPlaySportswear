<?php

$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$salePage = file_get_contents($root.'/resources/views/storefront/products/sale.blade.php');
$saleResults = file_get_contents($root.'/resources/views/storefront/products/_sale-results.blade.php');
$catalogService = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$productCard = file_get_contents($root.'/resources/views/components/storefront/product-card.blade.php');

foreach (compact('controller', 'salePage', 'saleResults', 'catalogService', 'productCard') as $name => $contents) {
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

$saleStart = strpos($controller, 'public function sale(ProductFilterRequest $request): View');
$helpersStart = strpos($controller, 'private function insertionIndices', $saleStart ?: 0);
$saleBlock = ($saleStart !== false && $helpersStart !== false)
    ? substr($controller, $saleStart, $helpersStart - $saleStart)
    : '';

$expect(str_contains($saleBlock, '$saleProductIds = $this->saleCampaigns->salePageProductIds();'), 'Sale uses the unique Sale-page product id scope.');
$expect(str_contains($saleBlock, '$saleProducts = $this->productCatalogService->saleProductsPaginated('), 'Sale uses the catalog paginator for one unique product collection.');
$expect(str_contains($saleBlock, '$saleMiddleInsertionIndices = $this->insertionIndices($saleProducts->count());'), 'Sale midpoint placement is computed from the unique products on the current page.');
$expect(str_contains($saleBlock, '\'saleProducts\' => $saleProducts'), 'Sale passes one product paginator to full and partial views.');
$expect(! str_contains($saleBlock, 'salePageCampaignGroups()'), 'Sale no longer builds campaign-specific product groups.');
$expect(! str_contains($saleBlock, '$sequence'), 'Sale no longer paginates campaign/product membership rows.');
$expect(! str_contains($saleBlock, '$campaignSections'), 'Sale no longer constructs campaign sections.');
$expect(! str_contains($saleBlock, 'new LengthAwarePaginator('), 'Sale no longer manually paginates duplicated campaign/product memberships.');

$expect(str_contains($saleResults, '@foreach ($saleProducts as $product)'), 'Sale results render one normal product collection.');
$expect(str_contains($saleResults, '<x-storefront.product-card :product="$product" />'), 'Sale reuses the canonical product card.');
$expect(str_contains($saleResults, ':banner="$saleMiddleBanner"'), 'Sale keeps the page-level middle promotion banner.');
$expect(str_contains($saleResults, 'np-promotion-midpoint-banner--cols-'), 'Sale keeps responsive midpoint banner placement.');
$expect(! str_contains($saleResults, 'campaignSections'), 'Sale result markup has no campaign sections.');
$expect(! str_contains($saleResults, 'np-sale-campaign-section'), 'Sale result markup has no campaign-name grouping wrapper.');
$expect(! str_contains($saleResults, '$campaign[\'name\']'), 'Sale result markup does not print campaign names.');

$expect(str_contains($salePage, '\'saleProducts\' => $saleProducts'), 'Sale page includes the unique product paginator in its result partial.');
$expect(! str_contains($salePage, '\'campaignSections\' => $campaignSections'), 'Sale page no longer passes campaign sections to the result partial.');

$paginateStart = strpos($catalogService, 'public function saleProductsPaginated(');
$nextMethod = strpos($catalogService, 'private function normalizeColorHex', $paginateStart ?: 0);
$paginateBlock = ($paginateStart !== false && $nextMethod !== false)
    ? substr($catalogService, $paginateStart, $nextMethod - $paginateStart)
    : '';
$expect(str_contains($paginateBlock, '->unique()'), 'Sale product scope is deduplicated before querying.');
$expect(str_contains($paginateBlock, '$this->applySaleCampaignPricing('), 'Every Sale card receives the authoritative per-product campaign pricing.');

$expect(str_contains($productCard, '$product[\'discount_percentage\']'), 'Canonical product card shows the product-specific discount percentage.');
$expect(str_contains($productCard, '$product[\'sale_badge_label\']'), 'Canonical product card consumes the product-specific Sale badge state.');

if ($failures !== []) {
    fwrite(STDERR, "Sale unique product listing regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Sale unique product listing regression passed.\n";
