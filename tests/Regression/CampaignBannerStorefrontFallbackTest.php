<?php

$root = dirname(__DIR__, 2);
$model = file_get_contents($root.'/app/Models/SaleCampaign.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/StoreSaleCampaignRequest.php');
$controller = file_get_contents($root.'/app/Http/Controllers/Admin/SaleCampaignController.php');
$service = file_get_contents($root.'/app/Services/Promotions/SaleCampaignService.php');
$bannerService = file_get_contents($root.'/app/Services/Promotions/SaleBannerService.php');
$productController = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$adminView = file_get_contents($root.'/resources/views/admin/promotions/sales/create.blade.php');
$bannerComponent = file_get_contents($root.'/resources/views/components/storefront/sale-banner.blade.php');

foreach (compact('model', 'request', 'controller', 'service', 'bannerService', 'productController', 'adminView', 'bannerComponent') as $name => $contents) {
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

foreach (['banner_heading', 'banner_alt_text', 'banner_cta_label', 'banner_destination_link', 'banner_mobile_image_path', 'banner_placements'] as $field) {
    $expect(str_contains($model, "'{$field}'"), "SaleCampaign fillable includes {$field}.");
    $expect(str_contains($request, "'{$field}'") || in_array($field, ['banner_mobile_image_path', 'banner_placements'], true), "Campaign request supports {$field}.");
    $expect(str_contains($controller, $field), "Campaign controller persists/handles {$field}.");
}

$expect(str_contains($request, 'banner_mobile_image'), 'Campaign request validates independent mobile banner uploads.');
$expect(str_contains($request, 'remove_banner_mobile_image'), 'Campaign request supports independent mobile removal.');
$expect(str_contains($request, 'PromotionBannerPlacement::ALL'), 'Campaign request validates against the shared placement vocabulary.');
$expect(str_contains($request, 'PublicUrl::isAllowed'), 'Campaign destination validation uses centralized PublicUrl.');

$expect(
    str_contains($service, "'campaign_banner'")
        && str_contains($service, "'desktop_image_url'")
        && str_contains($service, "'mobile_image_url'")
        && str_contains($service, "'placements'"),
    'SaleCampaignService exposes responsive placement-aware campaign banner payloads.'
);
$expect(str_contains($service, 'function bannerCandidates('), 'SaleCampaignService exposes ordered page-slot candidates.');

$resolveStart = strpos($bannerService, 'public function resolvePageSlot');
$compatStart = strpos($bannerService, 'public function resolveForCampaignPlacement', $resolveStart ?: 0);
$resolveBlock = ($resolveStart !== false && $compatStart !== false)
    ? substr($bannerService, $resolveStart, $compatStart - $resolveStart)
    : '';
$linkedPosition = strpos($resolveBlock, '$linked =');
$directPosition = strpos($resolveBlock, '$direct =');
$globalPosition = strpos($resolveBlock, '$global =');
$expect(
    $linkedPosition !== false && $directPosition !== false && $globalPosition !== false
        && $linkedPosition < $directPosition && $directPosition < $globalPosition,
    'Page-slot precedence is linked Banner, then Campaign direct media, then global Banner.'
);

$expect(
    str_contains($productController, '$saleTopBanner = $this->saleBanners->resolvePageSlot(')
        && str_contains($productController, '$saleMiddleBanner = $this->saleBanners->resolvePageSlot('),
    'Sale page resolves direct Campaign media only through the page-level slot resolver.'
);
$expect(
    ! str_contains($productController, "'top_banner'") && ! str_contains($productController, "'after_row_banner'"),
    'Sale campaign sections no longer carry independent/repeating banner payloads.'
);

$expect(str_contains($adminView, 'name="banner_mobile_image"'), 'Campaign editor exposes the mobile image upload.');
$expect(str_contains($adminView, "'placementState' => 'bannerPlacements'"), 'Campaign editor consumes the shared placement partial.');
$expect(
    str_contains($bannerComponent, 'desktop_image_url')
        && str_contains($bannerComponent, 'mobile_image_url')
        && str_contains($bannerComponent, '<picture>')
        && str_contains($bannerComponent, '<source'),
    'Shared storefront banner component renders responsive media with desktop fallback.'
);

$migrations = glob($root.'/database/migrations/*unify_campaign_and_banner_placements*.php') ?: [];
$expect(count($migrations) === 1, 'Unified placement/responsive Campaign media migration exists.');

if ($failures !== []) {
    fwrite(STDERR, "Campaign banner storefront fallback regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Campaign banner storefront fallback regression passed.\n";
