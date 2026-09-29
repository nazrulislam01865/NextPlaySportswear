<?php
$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/resources/css/storefront.css');
$required = [
    '.np-shipping-delivery-page', '.np-shipping-delivery-container',
    'width: min(var(--np-commerce-layout-max), calc(100% - var(--np-visible-page-inline-space)))',
    '.np-shipping-delivery-tabs', '.np-shipping-delivery-info-grid', '.np-shipping-delivery-timeline',
    '.np-shipping-delivery-main-grid', '.np-shipping-delivery-cta', '@media (max-width: 820px)',
];
foreach ($required as $snippet) {
    if (! str_contains($css, $snippet)) { fwrite(STDERR, "Shipping layout CSS missing: {$snippet}\n"); exit(1); }
}
if (preg_match('/\.np-shipping-delivery-container\s*\{[^}]*max-width\s*:\s*(?:1120|1200)px/s', $css)) {
    fwrite(STDERR, "Shipping page must not introduce a private narrow max-width.\n"); exit(1);
}

$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$compiledRelative = $manifest['resources/css/storefront.css']['file'] ?? null;
if (! is_string($compiledRelative) || $compiledRelative === '') {
    fwrite(STDERR, "Compiled storefront CSS entry missing from Vite manifest.\n"); exit(1);
}
$compiledPath = $root.'/public/build/'.$compiledRelative;
$compiledCss = is_file($compiledPath) ? file_get_contents($compiledPath) : '';
if (! str_contains($compiledCss, '.np-shipping-delivery-page')) {
    fwrite(STDERR, "Compiled storefront CSS is missing Shipping & Delivery styles.\n"); exit(1);
}

echo "Shipping & Delivery layout alignment check passed.\n";
