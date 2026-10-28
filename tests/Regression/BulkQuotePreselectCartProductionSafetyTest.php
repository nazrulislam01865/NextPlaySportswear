<?php

$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/BulkQuoteController.php');
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$actions = file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-actions.blade.php');
$picker = file_get_contents($root.'/resources/views/components/storefront/bulk-quote/product-information.blade.php');
$cart = file_get_contents($root.'/app/Services/Cart/CartService.php');
$checkout = file_get_contents($root.'/app/Services/Checkout/CheckoutService.php');
$view = file_get_contents($root.'/resources/views/components/storefront/cart/item-card.blade.php');

$assert = static function (bool $ok, string $message): void {
    if (! $ok) { fwrite(STDERR, "Quote + cart regression failed: {$message}\n"); exit(1); }
};

$assert(str_contains($builder, "['product' => \$product['id'] ?? \$product['slug']]"), 'product page sends product identifier');
$assert(str_contains($actions, "['product' => \$product['id'] ?? \$product['slug']]"), 'customizer sends product identifier');
$assert(str_contains($controller, 'ctype_digit($requestedProduct)'), 'quote accepts product id');
$assert(str_contains($controller, 'findBySlug($requestedProduct)'), 'old slug links remain functional');
$assert(str_contains($picker, "'product' => \$selectedProduct"), 'quote preselection is embedded for the existing picker');
$assert(str_contains($cart, 'allowMissingProductionRule: true'), 'saved cart item can be read with missing production rule');
$assert(str_contains($cart, "'configuration_error' => \$missingProductionRule"), 'missing rule is visible in cart');
$assert(str_contains($cart, "'checkout_ready' => count(\$items) > 0"), 'checkout is gated by cart validity');
$assert(str_contains($checkout, "\$this->cart->summary()['checkout_ready']"), 'checkout guard uses checkout-ready status');
$assert(str_contains($view, 'Update product options'), 'customer can reconfigure affected item');

echo "Quote preselect and cart production safety regression passed.\n";
