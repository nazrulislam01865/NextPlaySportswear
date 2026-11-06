<?php
/* Run: php tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php */
$root = dirname(__DIR__, 2);
$css = file_get_contents($root.'/resources/css/storefront.css');
$js = file_get_contents($root.'/resources/js/storefront.js');
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$compiledCss = file_get_contents($root.'/public/build/'.$manifest['resources/css/storefront.css']['file']);
$compiledJs = file_get_contents($root.'/public/build/'.$manifest['resources/js/storefront.js']['file']);
$failures = [];
$verify = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};
$verify(preg_match('/\.np-filter-shell--clean\s*\{[^}]*position:\s*sticky;[^}]*top:\s*var\(--np-catalog-filter-sticky-top,\s*8rem\);/s', $css) === 1, 'Desktop filter must stay sticky at all measured heights.');
$verify(str_contains($css, '.np-filter-shell--clean.np-filter-rail--long') && str_contains($css, 'position: sticky;'), 'Long rails must retain sticky positioning.');
$verify(preg_match('/\.np-filter-shell--clean \.np-catalog-filter-header\s*\{\s*position:\s*static;/s', $css) === 1, 'Heading must never overlay options.');
$verify(str_contains($css, '.np-filter-shell--clean .np-catalog-filter-scroll') && str_contains($css, 'overflow: visible;'), 'Desktop filters must not have a nested scrollbar.');
$verify(str_contains($js, "rail.addEventListener('wheel'") && ! str_contains($js, "window.addEventListener('scroll', onScroll"), 'Only deliberate filter navigation may shift a tall sticky rail.');
$verify(str_contains($compiledCss, 'overflow:visible!important') && str_contains($compiledJs, 'NEXTPLAY_FILTER_ANCHORED'), 'Vite manifest must reference updated compiled assets.');
foreach (['products/index.blade.php', 'products/sale.blade.php', 'categories/show.blade.php'] as $relative) {
    $verify(str_contains(file_get_contents($root.'/resources/views/storefront/'.$relative), 'np-filter-shell--clean'), "{$relative} must use the shared rail.");
}
if ($failures) { fwrite(STDERR, implode("\n", $failures)."\n"); exit(1); }
echo "Directional floating filter regression passed.\n";
