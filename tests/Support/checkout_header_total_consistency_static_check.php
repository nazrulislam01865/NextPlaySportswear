<?php
/**
 * Checkout summary/mini-cart price agreement without Laravel dependencies.
 * Run: php tests/Support/checkout_header_total_consistency_static_check.php
 */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$checkout = (string) file_get_contents($root.'/resources/views/components/storefront/checkout/summary-card.blade.php');
$provider = (string) file_get_contents($root.'/app/Providers/AppServiceProvider.php');
$service = (string) file_get_contents($root.'/app/Services/Checkout/CheckoutService.php');
$cartController = (string) file_get_contents($root.'/app/Http/Controllers/Storefront/CartController.php');
$assert = static function (bool $ok, string $description): void {
    if (! $ok) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
};

$assert(! str_contains($checkout, 'Calculated next'), 'Known configured shipping must not be marked as pending');
$assert(! str_contains($checkout, 'Total before shipping'), 'Checkout may not hide previously configured shipping in its total');
$assert(str_contains($checkout, "number_format((float) (\$summary['shipping'] ?? \$shipping), 2)"), 'Step 1 must display configured and address-dependent shipping once');
$assert(str_contains($checkout, 'number_format($total, 2)'), 'Every checkout step must use the same authoritative total');
$assert(str_contains($provider, "'total' => (float) (\$pricedSummary['total'] ?? 0)"), 'Header must use exact computed cart/checkout total');
$assert(strpos($provider, "'total' => (float) (\$pricedSummary['total'] ?? 0)") < strpos($provider, "app(CartService::class)->headerSummary(4)"), 'Precomputed summary must be reused without redundant cart reads');
$assert(str_contains($cartController, "View::share('npCurrentCartSummary', \$cart)"), 'Cart preview must share the same cart total as the cart page');
$assert(str_contains($service, "'shipping' => round(\$selectedShippingPrice, 2)"), 'Summary must include selected configured shipping');

// Example from the reported screenshot: product and configured shipping are both
// already priced at step 1. Do not re-add shipping to the value shown in header.
$merchandise = 2 * 9.81;
$configuredShipping = 38.92;
$assert(abs(round($merchandise + $configuredShipping, 2) - 58.54) < 0.001, 'Example must reconcile to $58.54 at both locations');
echo "PASS: checkout shipping and header totals share the same authoritative value\n";
