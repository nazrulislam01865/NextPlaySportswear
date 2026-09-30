<?php

$root = dirname(__DIR__, 2);
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$stepHeader = file_get_contents($root.'/resources/views/components/storefront/product/customizer/step-header.blade.php');
$orderSummary = file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(str_contains($builder, 'openCustomizerSteps: { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true }'), 'All six customizer steps start expanded.');
$expect(substr_count($builder, 'x-show="isCustomizerStepOpen(') >= 6, 'Each step panel uses independent open-state visibility.');
$expect(str_contains($stepHeader, '@click="toggleCustomizerStep({{ $number }})"'), 'Step headers independently toggle without forcing another step closed.');
$expect(str_contains($orderSummary, 'np-custom-order-mobile-capsule'), 'Mobile Custom Order capsule exists.');
$expect(str_contains($orderSummary, 'np-custom-order-panel'), 'Expandable mobile Custom Order panel exists.');
$expect(str_contains($orderSummary, 'money(productPriceAmount())'), 'Collapsed capsule shows the calculated product subtotal without duplicating shipping/total.');
$expect(! str_contains($orderSummary, 'np-custom-order-mobile-capsule__cart'), 'Collapsed mobile capsule does not include Add to Cart.');
$expect(str_contains($orderSummary, 'np-custom-order-mobile-capsule__chevron'), 'Collapsed mobile capsule keeps the expand/collapse control.');
$expect(str_contains($css, 'NEXTPLAY_PRODUCT_DETAIL_MOBILE_ORDERING_EXPERIENCE'), 'Mobile ordering refinement CSS marker exists.');
$expect(str_contains($css, '.np-proto-step-card { border-radius: 0;'), 'Customizer step cards use square corners.');
$expect(str_contains($css, '.np-custom-order-mobile-capsule'), 'Mobile bottom capsule has dedicated responsive styling.');
$expect(str_contains($css, 'min-height: 44px;') && str_contains($css, 'align-items: center;') && str_contains($css, 'align-self: center;'), 'Mobile capsule subtotal group and chevron are vertically centered.');
$expect(str_contains($css, 'position: fixed;') && str_contains($css, 'bottom: calc(10px + env(safe-area-inset-bottom));'), 'Mobile Custom Order capsule is fixed to the bottom safe area.');
$expect(str_contains($css, '.np-proto-size-table thead') && str_contains($css, 'display: none;'), 'Mobile size table no longer requires a horizontal header/table scroll.');
$expect(str_contains($css, '.np-proto-roster-table thead') && str_contains($css, 'display: none;'), 'Mobile roster table becomes a card layout instead of horizontal scroll.');
$expect(str_contains($css, '.np-product-information .np-product-detail-tabs') && str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));'), 'Product information tabs wrap on mobile without horizontal scrolling.');
$expect(str_contains($css, '.np-product-review-pricing .btn-secondary') && str_contains($css, 'display: inline-flex !important;'), 'Review Add to Cart remains visible on mobile.');
$expect(str_contains($builder, 'name="configuration_json" :value="configurationJson"'), 'Cart submission still posts the centralized configuration JSON.');
$expect(str_contains($builder, 'name="quantity" :value="totalQuantity()"'), 'Cart submission still posts the calculated total quantity.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail mobile ordering experience regression failed:\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "Product detail mobile ordering experience regression passed.\n";
