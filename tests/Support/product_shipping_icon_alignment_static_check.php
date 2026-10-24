<?php

// Dependency-free regression guard for storefront icon sizing and shipped assets.
$root = dirname(__DIR__, 2);
$source = file_get_contents($root.'/resources/css/storefront.css');
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$compiledFile = $manifest['resources/css/storefront.css']['file'] ?? null;
$compiled = $compiledFile && is_file($root.'/public/build/'.$compiledFile)
    ? file_get_contents($root.'/public/build/'.$compiledFile)
    : '';

$checks = [
    'delivery icon cell matches uploaded icon size' => 'grid-template-columns: 60px minmax(0, 1fr);',
    'delivery copy excludes icon wrappers' => '> span:not(.np-configurable-ui-icon)',
    'artwork list has no extra indentation' => '.np-proto-info-panel ul,',
    'uploaded artwork blends with existing panel background' => 'mix-blend-mode: multiply;',
    'shipping cards use container-responsive columns' => '@container np-shipping-choices (max-width: 1060px)',
    'delivery equation stacks in narrow containers' => '@container np-delivery-summary (max-width: 830px)',
];

foreach ($checks as $label => $selector) {
    if (! str_contains($source, $selector) || ! str_contains($compiled, $selector)) {
        fwrite(STDERR, "Missing source or compiled CSS for {$label}\n");
        exit(1);
    }
}

if (! str_contains($source, 'NEXTPLAY_PRODUCT_SHIPPING_ARTWORK_ALIGNMENT_2026_10_24')) {
    fwrite(STDERR, "Custom-order alignment patch marker missing.\n");
    exit(1);
}

echo "Product shipping icon alignment CSS and compiled asset passed.\n";
