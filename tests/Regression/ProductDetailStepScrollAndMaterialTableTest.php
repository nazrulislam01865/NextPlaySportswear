<?php

$root = dirname(__DIR__, 2);
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$priceTable = (string) file_get_contents($root.'/resources/views/components/storefront/product/customizer/price-table.blade.php');
$css = (string) file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($builder, 'scrollCustomizerStepIntoView(step'), 'Customizer has one centralized active-step scroll helper.');
$expect(str_contains($builder, "document.querySelector('.np-site-header')"), 'Active-step scrolling measures the sticky storefront header.');
$expect(str_contains($builder, 'requestAnimationFrame(() =>'), 'Active-step scrolling waits for the accordion layout to update.');
$expect(str_contains($builder, 'window.scrollTo({'), 'Active-step navigation scrolls the active card into the viewport.');
$expect(str_contains($builder, 'this.scrollCustomizerStepIntoView(next)'), 'openCustomizerStep invokes the centralized active-step scroll helper.');
$expect(str_contains($builder, 'this.openCustomizerStep(1)'), 'Start Customizing reuses the centralized step-opening behavior.');

$marker = 'NEXTPLAY_PRODUCT_DETAIL_STEP_SCROLL_AND_MATERIAL_POLISH';
$pos = strpos($css, $marker);
$fixCss = $pos === false ? '' : substr($css, $pos);
$expect($fixCss !== '', 'Step/material polish CSS block exists.');
$expect(str_contains($fixCss, '.np-product-material-card.is-selected .np-product-material-media'), 'Material selected-state media border is overridden in the final polish block.');
$expect(str_contains($fixCss, 'border: 1px solid var(--np-color-secondary)'), 'Selected material uses a thin 1px orange border.');
$expect(str_contains($fixCss, 'box-shadow: none'), 'Selected material no longer renders a double border/ring.');

$expect(! str_contains($priceTable, 'np-proto-table-help'), 'Pricing table no longer renders question-mark helper icons.');
$expect(! preg_match('/>\s*\?\s*<\/span>/', $priceTable), 'Pricing table contains no question-mark icon text.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail step-scroll/material-table regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail step-scroll/material-table regression passed.\n";
