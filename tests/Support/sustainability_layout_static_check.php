<?php
$root = dirname(__DIR__, 2);
$cssFile = $root.'/resources/css/storefront.css';
$css = file_get_contents($cssFile);
foreach (['.sustainability-page','--np-commerce-layout-max','--np-visible-page-inline-space','.sustainability-page__feature-row','.sustainability-page__informed-grid','.sustainability-page__cta'] as $needle) {
    if (! str_contains($css, $needle)) { fwrite(STDERR, "CSS missing {$needle}\n"); exit(1); }
}
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$entry = $manifest['resources/css/storefront.css'] ?? null;
$assets = [];
if (is_array($entry)) {
    if (isset($entry['file'])) $assets[] = $entry['file'];
    foreach (($entry['css'] ?? []) as $asset) $assets[] = $asset;
}
$found = false;
foreach (array_unique($assets) as $asset) {
    $path = $root.'/public/build/'.$asset;
    if (is_file($path) && str_contains(file_get_contents($path), '.sustainability-page')) { $found = true; break; }
}
if (! $found) { fwrite(STDERR, "Compiled CSS missing Sustainability styles\n"); exit(1); }
echo "sustainability layout static check: PASS\n";
