<?php
$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/public/css/nextplay-shipping-delivery-new.css');
$layout = file_get_contents($root.'/resources/views/components/layouts/storefront.blade.php');
$js = file_get_contents($root.'/public/js/nextplay-shipping-delivery-new.js');
foreach (['.np-shipping-page', '.np-shipping-container', '1180px', '.np-shipping-card', '.np-shipping-sidebar', '.np-shipping-table', 'var(--np-font-ui', 'var(--np-color-secondary', '@media'] as $snippet) {
    if (! str_contains($css, $snippet)) { fwrite(STDERR, "Shipping CSS missing: $snippet\n"); exit(1); }
}
if (! str_contains($layout, "request()->routeIs('shipping')") || ! str_contains($layout, 'nextplay-shipping-delivery-new.css')) {
    fwrite(STDERR, "Shipping assets must be loaded only on the shipping route.\n"); exit(1);
}
if (! str_contains($js, 'getBoundingClientRect()') || ! str_contains($js, "targets[targets.length - 1]")) {
    fwrite(STDERR, "Shipping sidebar position calculation missing.\n"); exit(1);
}
echo "New Shipping & Delivery design alignment check passed.\n";
