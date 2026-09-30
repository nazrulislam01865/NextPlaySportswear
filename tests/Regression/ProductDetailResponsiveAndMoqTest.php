<?php

$root = dirname(__DIR__, 2);
$js = file_get_contents($root.'/resources/js/storefront.js');
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$orderSummary = file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');
$cartService = file_get_contents($root.'/app/Services/Cart/CartService.php');
$css = file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(str_contains($js, 'orderQuantity: 0'), 'Products without size groups start with zero quantity.');
$expect(str_contains($js, 'Math.max(0, Math.min(maximum, Number(amount || 0)))'), 'Single quantity selector allows zero instead of snapping to MOQ.');
$expect(str_contains($js, 'Minimum order quantity is'), 'Add-to-cart validation explains the minimum order quantity.');
$expect(str_contains($builder, 'canAddToCart()') && str_contains($builder, 'return this.activeCustomizerStep === 6;'), 'Add to Cart becomes clickable on the review step so validation can notify the customer.');
$expect(str_contains($orderSummary, 'Minimum order'), 'Custom Order summary displays the minimum order quantity.');
$expect(str_contains($cartService, "abort_if(\$quantity < \$minimum"), 'Server rejects quantities below MOQ instead of silently increasing them.');
$expect(str_contains($css, 'NEXTPLAY_PRODUCT_DETAIL_RESPONSIVE_AND_MOQ'), 'Responsive/MOQ CSS refinement marker exists.');
$expect(str_contains($css, '.np-custom-order-card { order: 2;'), 'Small-screen Custom Order card follows the customizer instead of preceding it.');
$expect(str_contains($css, '.np-proto-step-navigation { grid-template-columns: 1fr;'), 'Small-screen customizer navigation stacks instead of squeezing two buttons.');
$expect(str_contains($css, '.np-product-detail-tabs {'), 'Responsive product-information tab treatment exists.');
$expect(str_contains($css, 'display: flex;'), 'Responsive product-information tabs use a scrollable flex row.');
$expect(str_contains($css, '.np-proto-size-summary-row { grid-template-columns: 1fr;'), 'Size summary stacks on narrow screens.');
$expect(str_contains($css, '.np-proto-artwork-tabs { grid-template-columns: 1fr;'), 'Artwork mode choices stack on narrow screens.');
$expect(str_contains($css, '.np-proto-choice-grid { grid-template-columns: 1fr;'), 'Production/shipping choices stack on narrow screens.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail responsive/MOQ regression failed:\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "Product detail responsive/MOQ regression passed.\n";
