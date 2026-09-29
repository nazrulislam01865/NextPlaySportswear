<?php
$root = dirname(__DIR__, 2);
$file = $root.'/resources/views/storefront/content/sustainability.blade.php';
if (! is_file($file)) { fwrite(STDERR, "Missing Sustainability storefront view\n"); exit(1); }
$view = file_get_contents($file);
foreach (['sustainability-page','approach.title','x-storefront.sustainability.feature-row','x-storefront.sustainability.info-card','sustainability-page__cta','primary_url','secondary_url'] as $needle) {
    if (! str_contains($view, $needle)) { fwrite(STDERR, "Storefront missing {$needle}\n"); exit(1); }
}
foreach (['feature-row.blade.php','info-card.blade.php','icon.blade.php'] as $component) {
    if (! is_file($root.'/resources/views/components/storefront/sustainability/'.$component)) { fwrite(STDERR, "Missing component {$component}\n"); exit(1); }
}
foreach (['materials-waste.webp','people-partners.webp','packaging-delivery.webp'] as $asset) {
    if (! is_file($root.'/public/images/sustainability/'.$asset)) { fwrite(STDERR, "Missing fallback asset {$asset}\n"); exit(1); }
}
echo "sustainability page static check: PASS\n";
