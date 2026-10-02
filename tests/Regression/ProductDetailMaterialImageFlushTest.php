<?php

$root = dirname(__DIR__, 2);
$css = (string) file_get_contents($root.'/resources/css/storefront.css');
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$marker = 'NEXTPLAY_PRODUCT_DETAIL_MATERIAL_IMAGE_FLUSH';
$pos = strpos($css, $marker);
$fixCss = $pos === false ? '' : substr($css, $pos);

$expect($fixCss !== '', 'Final material-image CSS block exists.');
$expect((bool) preg_match('/\.np-product-material-grid\s*\{[^}]*grid-template-columns:\s*repeat\(auto-fill,\s*116px\)/s', $fixCss), 'Desktop material grid auto-fills the available width instead of being limited to three columns.');
$expect((bool) preg_match('/\.np-product-material-card\s*\{[^}]*width:\s*116px/s', $fixCss), 'Desktop material cards use one consistent width.');
$expect((bool) preg_match('/\.np-product-material-media\s*\{[^}]*width:\s*116px[^}]*aspect-ratio:\s*29\s*\/\s*21/s', $fixCss), 'Material media uses one predictable thumbnail aspect ratio.');
$expect((bool) preg_match('/\.np-product-material-media img\s*\{[^}]*width:\s*100%[^}]*height:\s*100%[^}]*object-fit:\s*cover/s', $fixCss), 'Every material image fills the same media frame without using its intrinsic height.');
$expect((bool) preg_match('/\.np-product-material-placeholder[\s\S]*height:\s*100%/s', $fixCss), 'Fallback swatches/placeholders fill the same media frame.');
$expect(str_contains($builder, 'np-product-material-media'), 'Material media markup remains reusable in the builder.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail material-image alignment regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail material-image alignment regression passed.\n";
