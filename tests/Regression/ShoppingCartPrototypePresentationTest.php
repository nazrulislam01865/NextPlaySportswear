<?php

$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/storefront/cart/index.blade.php');
$item = file_get_contents($root.'/resources/views/components/storefront/cart/item-card.blade.php');
$summary = file_get_contents($root.'/resources/views/components/storefront/cart/summary-card.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/CartController.php');
$service = file_get_contents($root.'/app/Services/Cart/CartService.php');

foreach (compact('view', 'item', 'summary', 'css', 'controller', 'service') as $name => $contents) {
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

$expect(str_contains($view, 'NEXTPLAY_CART_PROTOTYPE'), 'Cart page has the prototype implementation marker.');
$expect(str_contains($view, '<x-storefront.cart.item-card'), 'Filled cart reuses the cart item component.');
$expect(str_contains($view, '<x-storefront.cart.summary-card'), 'Filled cart reuses the summary component.');
$expect(str_contains($view, 'data-cart-empty-state'), 'Empty-cart prototype state is present.');
$expect(str_contains($view, 'Your cart is empty'), 'Empty-cart heading matches the prototype.');
$expect(str_contains($view, 'Find something you love and add it to your cart.'), 'Empty-cart description matches the prototype.');
$expect(str_contains($view, 'Shop Products'), 'Empty-cart CTA matches the prototype.');
$expect(! str_contains($view, 'Preview Cart Design'), 'Prototype-only preview CTA is removed from the empty state.');
$expect(! str_contains($view, 'Recommended Products'), 'Recommended products section is not shown on the prototype cart page.');
$expect(! str_contains($controller, "collect(\$this->products->featured())"), 'Cart page no longer runs the unused recommended-products query.');
$expect(! str_contains($view, '<x-storefront.cart.trust-panel'), 'Trust panel is not shown in the prototype cart layout.');
$expect(! str_contains($view, 'Review custom order'), 'Legacy cart intro is removed.');
$expect(! str_contains($view, '1. Cart'), 'Legacy checkout progress strip is removed.');

$expect(str_contains($item, 'Customization details saved'), 'Cart item shows the compact customization-saved status.');
$expect(str_contains($item, 'data-cart-quantity-form'), 'Quantity update forms remain wired.');
$expect(str_contains($item, 'data-cart-remove-form'), 'Remove form remains wired.');
$expect(str_contains($item, 'data-cart-item-money="line_total"'), 'Line total remains AJAX-refreshable.');
$expect(str_contains($item, "'line_subtotal'") && str_contains($item, "'customization_total'"), 'Prototype item total excludes shipping that is calculated at checkout.');
$expect(! str_contains($item, 'Edit Options'), 'Prototype cart row does not show the legacy Edit Options action.');
$expect(! str_contains($item, 'Production &amp; shipping'), 'Prototype cart row does not expose the legacy production panel.');

$expect(str_contains($summary, '>Order Summary<'), 'Summary title matches the prototype.');
$expect(str_contains($summary, '>Items ('), 'Summary shows item quantity in the Items row.');
$expect(str_contains($summary, 'Calculated at checkout'), 'Summary shipping copy matches the prototype.');
$expect(str_contains($summary, 'Estimated subtotal'), 'Summary subtotal label matches the prototype.');
$expect(str_contains($service, "'estimated_subtotal' => round(max(0, \$merchandiseTotal - \$discount), 2)"), 'Cart summary exposes the estimated subtotal used by the prototype.');
$expect(str_contains($summary, 'Proceed to Checkout'), 'Summary keeps the checkout CTA.');
$expect(str_contains($summary, 'Continue Shopping'), 'Summary keeps the continue-shopping CTA.');
$expect(str_contains($summary, 'Have a promo code?'), 'Promo code is presented as the prototype accordion row.');
$expect(str_contains($summary, 'data-coupon-form'), 'Promo code form remains functional.');
$expect(str_contains($summary, 'data-coupon-remove-form'), 'Promo code removal remains functional.');
$expect(! str_contains($summary, 'Request Bulk Quote'), 'Bulk quote CTA is removed from the prototype summary.');
$expect(! str_contains($summary, 'Grand total'), 'Legacy navy grand-total panel is removed.');
$expect(! str_contains($summary, 'payment_badges'), 'Payment badges are removed from the prototype summary.');

$expect(str_contains($css, 'NEXTPLAY_CART_PROTOTYPE'), 'Centralized cart prototype CSS block is present.');
$expect(str_contains($css, '.np-cart-page'), 'Cart prototype uses a page-scoped centralized CSS namespace.');

if ($failures !== []) {
    fwrite(STDERR, "Shopping cart prototype regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Shopping cart prototype regression passed.\n";
