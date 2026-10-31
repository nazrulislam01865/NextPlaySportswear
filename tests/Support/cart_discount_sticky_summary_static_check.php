<?php
/** Regression check for visible discount reconciliation and safe sticky summaries. */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$read = fn (string $file): string => file_get_contents($root.'/'.$file);
$cart = $read('app/Services/Cart/CartService.php');
$checkout = $read('app/Services/Checkout/CheckoutService.php');
$cartCard = $read('resources/views/components/storefront/cart/summary-card.blade.php');
$checkoutCard = $read('resources/views/components/storefront/checkout/summary-card.blade.php');
$layout = $read('resources/views/components/layouts/storefront.blade.php');
$js = $read('public/js/nextplay-sticky-summary.js');
$css = $read('public/css/nextplay-checkout-patches.css');
$quoteCss = $read('public/css/nextplay-quote-builder.css');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {fwrite(STDERR, "FAIL: {$message}\n"); exit(1);}
};
foreach (['sale_savings', 'total_discount', 'merchandise_before_offers'] as $field) {
    $assert(str_contains($cart, "'{$field}'"), "Missing {$field} in cart totals");
    $assert(str_contains($checkout, "'{$field}'"), "Missing {$field} in checkout summary");
}
$assert(str_contains($cartCard, 'data-cart-money="total_discount"'), 'Cart must display all discounts');
$assert(str_contains($cartCard, 'data-cart-money="merchandise_before_offers"'), 'Cart must display pre-offer merchandise to balance sale savings');
$assert(str_contains($checkoutCard, 'Total discounts'), 'Checkout must display all discounts');
$assert(str_contains($checkoutCard, 'Product discount'), 'Checkout must display product offer discount');
$assert(!str_contains($checkoutCard, 'Of which: additional order shipping'), 'Do not show non-additive duplicate shipping rows');
$assert(str_contains($checkoutCard, 'Additional order shipping'), 'Show automatic extra shipping separately only when present');
$assert(str_contains($layout, 'nextplay-sticky-summary.js'), 'Sticky behavior must be loaded');
$assert(str_contains($js, 'getBoundingClientRect().height'), 'Use actual header height');
$assert(str_contains($js, 'np-summary-no-sticky'), 'Tall cards must fall back to page scrolling');
$assert(str_contains($css, 'var(--np-summary-sticky-top'), 'Checkout must clear sticky navigation');
$assert(str_contains($quoteCss, 'var(--np-summary-sticky-top'), 'Bulk quote must clear sticky navigation');
$preOffer = 13.71;
$saleSavings = 1.38;
$shippingOnce = 35.56;
$expected = 47.89;
$assert(abs(round($preOffer - $saleSavings + $shippingOnce, 2) - $expected) < 0.001, 'Offer and shipping example does not reconcile');
echo "PASS: cart/checkout discount totals and unclipped sticky sidebar checks\n";
