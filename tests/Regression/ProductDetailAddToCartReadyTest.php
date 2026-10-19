<?php

$root = dirname(__DIR__, 2);
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$summary = (string) file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');
$storefrontJs = (string) file_get_contents($root.'/resources/js/storefront.js');

$assert = static function (bool $ok, string $message): void {
    if (! $ok) {
        fwrite(STDERR, "Product Add to Cart regression failed: {$message}\n");
        exit(1);
    }
};

$assert(str_contains($builder, '$reviewStep = $showRosterStep ? 6 : 5;'), 'review step should be 5 when roster is disabled and 6 when enabled');
$assert(str_contains($builder, 'Number(this.activeCustomizerStep) === Number(this.maxCustomizerStep)'), 'review submit uses current maximum step, never a hardcoded six');
$assert(str_contains($builder, '|| this.validationIssue() === null'), 'completed configurations can submit without requiring an accordion click');
$assert(! str_contains($builder, 'return this.activeCustomizerStep === 6;'), 'old hardcoded step-six guard must not return');
$assert(str_contains($builder, '@submit="if(!validate()) $event.preventDefault()"'), 'form keeps client-side validation');
$assert(str_contains($builder, ':roster-step="$rosterStep" :artwork-step="$artworkStep" :production-step="$productionStep"'), 'sidebar shares the same dynamic steps as the product body');
$assert(str_contains($summary, '@if($rosterStep)'), 'roster summary is hidden if product roster is disabled');
$assert(str_contains($summary, 'openCustomizerStep({{ $artworkStep }})'), 'artwork summary navigates to the correct step');
$assert(str_contains($summary, 'openCustomizerStep({{ $productionStep }})'), 'production summary navigates to the correct step');
$assert(str_contains($summary, 'validationIssue()?.message'), 'incomplete configurations show a specific reason');
$assert(str_contains($storefrontJs, 'config.production_methods_enabled && !this.currentProductionOptions().length'), 'client validates quantity-based production rules consistently with the server');

echo "Product Add to Cart regression passed.\n";
