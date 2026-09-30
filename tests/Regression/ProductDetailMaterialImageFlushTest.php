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

$expect($fixCss !== '', 'Final flush material-image CSS block exists.');
$expect(str_contains($fixCss, '.np-product-material-media'), 'Material media wrapper is explicitly normalized.');
$expect(str_contains($fixCss, 'padding: 0'), 'Material media wrapper has no image padding.');
$expect(str_contains($fixCss, 'margin: 0'), 'Material media wrapper has no image margin.');
$expect(str_contains($fixCss, '.np-product-material-media img'), 'Material image has an explicit flush-fill rule.');
$expect(str_contains($fixCss, 'display: block'), 'Material image is block-level so no inline image gap remains.');
$expect(str_contains($fixCss, '.np-product-material-placeholder'), 'Fallback placeholder has a dedicated full-frame rule.');
$expect((bool) preg_match('/\.np-product-material-placeholder\s*\{[^}]*width:\s*100%[^}]*height:\s*84px/s', $fixCss), 'Fallback placeholder fills the complete 116x84 material image frame.');
$expect(str_contains($builder, 'np-product-material-media'), 'Material media markup remains reusable in the builder.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail material-image flush regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail material-image flush regression passed.\n";
