<?php
/**
 * Checkout-only presentation regression. No Laravel vendor install required.
 * Run: php tests/Support/checkout_typography_template_static_check.php
 */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$source = file_get_contents($root.'/resources/css/nextplay-checkout.css');
$public = file_get_contents($root.'/public/css/nextplay-checkout.css');
$layout = file_get_contents($root.'/resources/views/components/layouts/storefront.blade.php');
$referral = file_get_contents($root.'/resources/views/storefront/checkout/referral.blade.php');
$success = file_get_contents($root.'/resources/views/storefront/checkout/success.blade.php');
$fail = static function (string $reason): never { fwrite(STDERR, "FAIL: $reason\n"); exit(1); };
$need = static function (bool $ok, string $reason) use ($fail): void { if (! $ok) $fail($reason); };
$need($source === $public, 'Public CSS must be identical to source for no-build deployment.');
$marker = '/* CHECKOUT-ONLY TYPOGRAPHY ALIGNMENT - template v1.1 / 2026-10-31';
$need(substr_count($source, $marker) === 1, 'Exactly one checkout-only template block must exist.');
$overrides = substr($source, strpos($source, $marker));
$need(str_contains($layout, "request()->routeIs('checkout.*')"), 'Stylesheet must remain scoped to checkout routes.');
$need(str_contains($layout, '20261031templatev11'), 'Checkout CSS cache version must change.');
foreach (['.np-checkout__heading h1', '.np-referral-checkout-top h1', '.np-checkout-success h1',
    'var(--nps-h1)', 'var(--nps-h1-mobile)', 'var(--nps-link)', 'var(--nps-muted)',
    'var(--nps-error)', 'var(--nps-success)', 'var(--nps-button-size)',
    'var(--nps-button-weight)', 'font-weight:700', 'line-height:1.6'] as $token) {
    $need(str_contains($overrides, $token), 'Missing central template role '.$token);
}
$need(str_contains($referral, '<h1>Secure checkout</h1>'), 'Referral page title must use sentence case.');
$need(str_contains($success, 'class="np-checkout-success bg-slate-50 py-16"'), 'Order success typography must be scoped.');
$need(str_contains($success, '>Thank you</h1>'), 'Order success title must use sentence case.');
/* Check SELECTORS, not comments or declarations. Do not restyle any form control. */
preg_match_all('/(?:^|\})\s*([^{}]+)\s*\{/m', $overrides, $parts);
foreach ($parts[1] as $selector) {
    $selector = trim($selector);
    $selector = preg_replace('~/\*.*?\*/~s', '', $selector);
    $need(! preg_match('/(^|[^a-z-])(input|select|textarea)(?![a-z-])/i', $selector),
        'Form-control selector introduced: '.$selector);
    $need(! preg_match('/:(focus|checked|invalid|read-only|placeholder)/i', $selector),
        'Form-control-state selector introduced: '.$selector);
}
$need(! str_contains($overrides, '.np-product-card'), 'Product cards must not be touched.');
$need(! str_contains($overrides, '.np-filter'), 'Catalog filters must not be touched.');
$need(str_contains($source, '.np-checkout__form :is(input[type=text],input[type=email],input[type=tel],select,textarea)'),
    'The original checkout form-control rules must remain in place.');
foreach (['information','shipping-address','billing-address','shipping-method','review','payment-method','referral','success'] as $name) {
    $need(is_file($root.'/resources/views/storefront/checkout/'.$name.'.blade.php'), 'Checkout screen missing: '.$name);
}
echo "PASS: checkout typography template/static/form-control isolation checks\n";
