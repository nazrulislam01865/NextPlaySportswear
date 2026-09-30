<?php

$root = dirname(__DIR__, 2);
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$orderSummary = file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(str_contains($builder, 'this.openCustomizerSteps[current] = false;'), 'Next navigation collapses the step the customer just completed.');
$expect(! str_contains($orderSummary, 'np-custom-order-mobile-capsule__cart'), 'Mobile capsule does not expose Add to Cart.');
$expect(str_contains($orderSummary, 'np-custom-order-mobile-capsule__chevron'), 'Mobile capsule retains the expand/collapse control.');
$expect(str_contains($css, 'NEXTPLAY_PRODUCT_DETAIL_MOBILE_USABILITY_REFINEMENT'), 'Mobile follow-up responsive refinement marker exists.');
$expect(str_contains($css, '@media (max-width: 1023px)') && str_contains($css, '.np-proto-roster-table thead { display: none; }'), 'Player roster becomes a responsive card layout on tablet and mobile.');
$expect(str_contains($css, '.np-product-review-pricing .btn-secondary') && str_contains($css, 'display: inline-flex !important;'), 'Review Add to Cart is visible on mobile.');
$expect(preg_match('/\.np-custom-order-panel\s+\.np-custom-order-add-to-cart\s*\{\s*display:\s*none\s*!important;/s', $css) !== 1, 'Expanded mobile summary keeps its Add to Cart action visible.');
$expect(str_contains($css, '.np-product-information-card .rounded-2xl') && str_contains($css, 'border-radius: 0 !important;'), 'Product Information cards use square corners.');
$expect(str_contains($css, '.np-product-page .product-gallery-frame') && str_contains($css, 'border: 0 !important;') && str_contains($css, 'box-shadow: none !important;'), 'Main product image frame has no decorative border or shadow.');
$expect(str_contains($css, '.np-product-page .np-product-gallery-image') && str_contains($css, 'padding: 0 !important;') && str_contains($css, 'margin: 0 !important;'), 'Main product image renders without padding or margin.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail mobile follow-up regression failed:\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "Product detail mobile follow-up regression passed.\n";
