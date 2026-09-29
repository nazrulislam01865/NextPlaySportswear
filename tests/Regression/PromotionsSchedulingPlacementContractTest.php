<?php

$root = dirname(__DIR__, 2);
$read = static function (string $path) use ($root): string {
    $contents = file_get_contents($root.'/'.$path);
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$path}.\n");
        exit(1);
    }
    return $contents;
};

$schedule = is_file($root.'/app/Services/Promotions/PromotionScheduleService.php')
    ? $read('app/Services/Promotions/PromotionScheduleService.php') : '';
$codeGenerator = is_file($root.'/app/Services/Promotions/SaleCampaignCodeGenerator.php')
    ? $read('app/Services/Promotions/SaleCampaignCodeGenerator.php') : '';
$placement = $read('app/Support/PromotionBannerPlacement.php');
$positionService = $read('app/Services/Promotions/PromotionBannerPositionService.php');
$campaignService = $read('app/Services/Promotions/SaleCampaignService.php');
$bannerService = $read('app/Services/Promotions/SaleBannerService.php');
$nav = $read('app/Services/Catalog/NavigationService.php');
$campaignRequest = $read('app/Http/Requests/Admin/StoreSaleCampaignRequest.php');
$campaignController = $read('app/Http/Controllers/Admin/SaleCampaignController.php');
$campaignEditor = $read('resources/views/admin/promotions/sales/create.blade.php');
$campaignIndex = $read('resources/views/admin/promotions/sales/index.blade.php');
$bannerModel = $read('app/Models/SaleBanner.php');
$bannerRequest = $read('app/Http/Requests/Admin/SaveSaleBannerRequest.php');
$bannerEditor = $read('resources/views/admin/promotions/banners/_editor.blade.php');
$bannerPreview = $read('resources/views/admin/promotions/banners/_placement-preview.blade.php');
$sharedPlacementFields = $read('resources/views/admin/promotions/_banner-placement-fields.blade.php');
$productController = $read('app/Http/Controllers/Storefront/ProductController.php');
$categoryController = $read('app/Http/Controllers/Storefront/CategoryController.php');
$saleResults = $read('resources/views/storefront/products/_sale-results.blade.php');
$allProductsResults = $read('resources/views/storefront/products/_results.blade.php');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(str_contains($schedule, 'class PromotionScheduleService'), 'Central PromotionScheduleService exists.');
foreach (['campaignStatus', 'campaignIsActive', 'bannerStatus', 'bannerIsActive'] as $method) {
    $expect(str_contains($schedule, 'function '.$method.'('), "Schedule service implements {$method}().");
}
$expect(str_contains($schedule, "'Paused today'"), 'Schedule service supports Paused today.');
$expect(str_contains($campaignService, 'PromotionScheduleService'), 'SaleCampaignService delegates runtime eligibility to schedule service.');
$expect(str_contains($bannerService, 'PromotionScheduleService'), 'SaleBannerService delegates runtime eligibility to schedule service.');

$expect(! str_contains($nav, 'hasActiveSalePageCampaign()'), 'Sale navigation remains independent of current Campaign activity.');
$expect(str_contains($nav, "'route_name' => 'sale.index'"), 'Navigation retains the Sale route item.');

$expect(str_contains($codeGenerator, 'class SaleCampaignCodeGenerator'), 'SaleCampaignCodeGenerator exists.');
$expect(str_contains($campaignController, 'SaleCampaignCodeGenerator'), 'Campaign controller keeps server-side code generation.');
$expect(! str_contains($campaignRequest, "'internal_code' => ["), 'Campaign request does not accept internal_code as editable data.');
$expect(! str_contains($campaignEditor, 'name="internal_code"'), 'Campaign editor does not submit internal_code.');
$expect(str_contains($campaignEditor, 'Generated automatically when saved') || str_contains($campaignEditor, 'readonly'), 'Campaign editor keeps internal code server-owned/read-only.');

foreach ([
    "SALE_TOP = 'sale_top'",
    "SALE_MIDDLE = 'sale_middle'",
    "ALL_PRODUCTS_TOP = 'all_products_top'",
    "ALL_PRODUCTS_MIDDLE = 'all_products_middle'",
    "CATEGORY_TOP = 'category_top'",
] as $definition) {
    $expect(str_contains($placement, $definition), "Shared placement vocabulary defines {$definition}.");
}
$expect(str_contains($bannerModel, 'PromotionBannerPlacement::SALE_MIDDLE'), 'SaleBanner aliases the shared middle placement.');
$expect(str_contains($bannerModel, 'PromotionBannerPlacement::ALL_PRODUCTS_TOP'), 'SaleBanner aliases All Products top placement.');
$expect(str_contains($bannerModel, 'PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE'), 'SaleBanner aliases All Products middle placement.');
$expect(str_contains($bannerRequest, 'PublicUrl::isAllowed'), 'Banner request keeps centralized PublicUrl validation.');
$expect(str_contains($positionService, 'function evenMiddleRow(') && str_contains($positionService, 'function groupedInsertionPoint('), 'Shared midpoint service owns responsive insertion calculation.');

$expect(str_contains($campaignEditor, "admin.promotions._banner-placement-fields"), 'Campaign editor consumes shared placement controls.');
$expect(str_contains($bannerEditor, "admin.promotions._banner-placement-fields"), 'Banner editor consumes shared placement controls.');
$expect(str_contains($sharedPlacementFields, 'PromotionBannerPlacement::labels()'), 'Shared placement control uses the centralized labels.');
$expect(str_contains($campaignEditor, 'recommended ratio 24:5') && str_contains($campaignEditor, 'recommended ratio 5:4'), 'Campaign editor uses ratio guidance.');
$expect(str_contains($bannerEditor, 'recommended ratio 24:5') && str_contains($bannerEditor, 'recommended ratio 5:4'), 'Banner editor uses ratio guidance.');
$expect(! str_contains($bannerPreview, 'after every second product row'), 'Banner preview no longer advertises repeating-row placement.');

$expect(str_contains($bannerService, 'function resolvePageSlot('), 'Banner service resolves one page slot across Campaign candidates.');
$expect(str_contains($bannerService, 'PublicUrl::isAllowed'), 'Banner payload still normalizes unsafe legacy destinations.');
$expect(str_contains($campaignService, 'function bannerCandidates('), 'Campaign service supplies page-slot candidates.');
$expect(str_contains($productController, 'PromotionBannerPlacement::SALE_TOP') && str_contains($productController, 'PromotionBannerPlacement::SALE_MIDDLE'), 'Sale page resolves explicit top/middle slots.');
$expect(str_contains($productController, 'PromotionBannerPlacement::ALL_PRODUCTS_TOP') && str_contains($productController, 'PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE'), 'All Products resolves explicit top/middle slots.');
$expect(str_contains($categoryController, 'PromotionBannerPlacement::CATEGORY_TOP') && str_contains($categoryController, 'resolvePageSlot('), 'Category resolves one category-aware top slot.');
$expect(! str_contains($productController, "'top_banner'") && ! str_contains($productController, "'after_row_banner'"), 'Per-Campaign Sale banner ownership has been removed.');
$expect(! str_contains($saleResults, '% 2') && ! str_contains($saleResults, '% 4') && ! str_contains($saleResults, '% 6') && ! str_contains($saleResults, '% 8') && ! str_contains($saleResults, '% 10'), 'Sale results contain no repeating interval placement logic.');
$expect(str_contains($saleResults, 'np-promotion-midpoint-banner') && str_contains($allProductsResults, 'np-promotion-midpoint-banner'), 'Sale and All Products consume the shared breakpoint midpoint classes.');

$placementMigrations = glob($root.'/database/migrations/*unify_campaign_and_banner_placements*.php') ?: [];
$expect(count($placementMigrations) === 1, 'Unified placement migration exists.');
$codeMigrations = glob($root.'/database/migrations/*backfill_sale_campaign_internal_codes*.php') ?: [];
$expect(count($codeMigrations) === 1, 'Campaign internal-code backfill migration remains intact.');
$expect(! str_contains($campaignIndex, '$isScheduled ='), 'Campaign list still avoids runtime status logic in Blade.');

if ($failures !== []) {
    fwrite(STDERR, "Promotions scheduling/placement contract failed:\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "Promotions scheduling/placement contract passed.\n";
