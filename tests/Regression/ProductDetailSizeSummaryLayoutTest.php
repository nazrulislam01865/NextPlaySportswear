<?php

$root = dirname(__DIR__, 2);
$blade = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$css = (string) file_get_contents($root.'/resources/css/storefront.css');
$icon = (string) file_get_contents($root.'/resources/views/components/storefront/product/ui-icon.blade.php');

$checks = [
    'The size summary has its own inline-size query container.' => str_contains($blade, 'class="np-proto-size-summary-layout"') && str_contains($css, 'container: np-size-summary / inline-size;'),
    'Four summary cells have equal widths on large panels.' => str_contains($css, 'grid-template-columns: repeat(4, minmax(0, 1fr));'),
    'Three-cell summaries without fabric remain equally sized.' => str_contains($blade, 'np-proto-size-summary-row--no-fabric') && str_contains($css, 'grid-template-columns: repeat(3, minmax(0, 1fr));'),
    'A desktop separator remains between Sizes Available and Minimum Order Quantity.' => str_contains($css, '.np-proto-size-summary-row > .np-proto-size-summary-fact:nth-last-child(3)') && str_contains($css, 'border-right: 1px solid #e6ebf1;'),
    'The divider does not create a stray line at two- or one-column sizes.' => str_contains($css, '.np-proto-size-summary-row:not(.np-proto-size-summary-row--no-fabric) > .np-proto-size-summary-fact:nth-last-child(3)') && str_contains($css, '@container np-size-summary (max-width: 520px)'),
    'Medium panels display two equal columns.' => str_contains($css, '@container np-size-summary (max-width: 860px)') && str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));'),
    'Small panels display one column.' => str_contains($css, '@container np-size-summary (max-width: 520px)'),
    'Long size descriptions can wrap without forcing narrow grid columns.' => str_contains($css, 'overflow-wrap: anywhere;'),
    'Unavailable fabric thumbnails are hidden, instead of drawing empty gray placeholders.' => str_contains($blade, 'is-image-empty') && str_contains($blade, 'class="np-custom-order-fabric-preview" x-show='),
    'Uploaded configurable icons have a transparent, frameless presentation.' => str_contains($icon, 'background: transparent;') && str_contains($icon, 'border: 0;') && str_contains($icon, 'box-shadow: none;'),
    'Product summary uses the same 64px x 56px media size as the fabric thumbnail.' => str_contains($css, 'width: 64px;') && str_contains($css, 'height: 56px;') && str_contains($icon, 'width: 64px;'),
    'Shipping-method image/icon backgrounds are removed.' => str_contains($css, '/* Shipping-method image/icon previews use the uploaded artwork, not a tinted tile. */'),
];

$errors = array_keys(array_filter($checks, static fn (bool $passed): bool => ! $passed));
if ($errors !== []) {
    fwrite(STDERR, "Product detail size-summary layout regression failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

echo "Product detail size-summary layout regression passed.\n";
