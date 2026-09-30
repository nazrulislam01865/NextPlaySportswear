<?php

$root = dirname(__DIR__, 2);
$show = file_get_contents($root.'/resources/views/storefront/products/show.blade.php');
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$gallery = file_get_contents($root.'/resources/views/components/storefront/product/gallery.blade.php');
$details = file_get_contents($root.'/resources/views/components/storefront/product/details.blade.php');
$signals = file_get_contents($root.'/resources/views/components/storefront/product/purchase-signals.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$stepper = file_get_contents($root.'/resources/views/components/storefront/product/customizer/stepper.blade.php');
$orderSummary = file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');

foreach (compact('show', 'builder', 'gallery', 'details', 'signals', 'css', 'stepper', 'orderSummary') as $name => $contents) {
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$name} source.\n");
        exit(1);
    }
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($builder, 'NEXTPLAY_PRODUCT_DETAIL_PROTOTYPE'), 'Product builder has the prototype implementation marker.');
$expect(str_contains($css, 'NEXTPLAY_PRODUCT_DETAIL_PROTOTYPE'), 'Centralized product-detail prototype CSS block is present.');
$expect(str_contains($builder, 'np-product-detail-hero'), 'Product hero is rendered inside the authoritative builder state.');
$expect(! str_contains($show, 'x-data="productBuilder('), 'Product page does not create a second productBuilder state for the hero.');
$expect(substr_count($builder, 'x-data="productBuilderFabricPricing(') === 1, 'Product builder has exactly one authoritative Alpine state.');
$expect(str_contains($builder, 'activeCustomizerStep: 1'), 'Configurator owns one active six-step state.');
$expect(str_contains($builder, 'startCustomizing()'), 'Configurator exposes the hero-to-builder transition without a second state object.');

$stepSources = $builder.$stepper;
foreach (['Price & Fabric', 'Sizes & Quantities', 'Player Names & Numbers', 'Upload Artwork', 'Production & Shipping', 'Review & Add to Cart'] as $label) {
    $expect(str_contains($stepSources, $label), "Six-step configurator includes {$label}.");
}

$expect(str_contains($builder, 'Start Customizing'), 'Hero contains the Start Customizing CTA.');
$expect(str_contains($builder, 'Request Bulk Quote'), 'Hero contains the Request Bulk Quote CTA.');
$expect(str_contains($orderSummary, 'Your Custom Order'), 'Sticky order summary matches prototype title.');
$expect(str_contains($orderSummary, 'Selected Fabric'), 'Sticky order summary exposes selected fabric.');
$expect(str_contains($orderSummary, 'Selected Sizes &amp; Quantities'), 'Sticky order summary exposes size quantities.');
$expect(str_contains($orderSummary, 'Production Lead Time'), 'Sticky order summary exposes the production lead time.');
$expect(str_contains($orderSummary, 'Shipping'), 'Sticky order summary exposes shipping.');
$expect(str_contains($orderSummary, 'Estimated Total'), 'Review sidebar includes the prototype estimated-total state.');
$expect(str_contains($builder, 'np-product-review-card'), 'Review step contains the prototype order summary card.');
$expect(str_contains($builder, 'Free design review'), 'Review step keeps prototype trust messaging.');
$expect(str_contains($builder, 'Secure checkout'), 'Review step keeps secure checkout trust messaging.');
$expect(str_contains($builder, 'Worldwide shipping'), 'Review step keeps worldwide shipping trust messaging.');

$expect(str_contains($gallery, 'np-product-gallery-stage'), 'Gallery uses the prototype image stage.');
$expect(str_contains($gallery, 'Previous product image'), 'Gallery provides a previous-image control.');
$expect(str_contains($gallery, 'Next product image'), 'Gallery provides a next-image control.');
$expect(str_contains($gallery, 'np-product-gallery-zoom'), 'Gallery includes the prototype zoom control.');
$expect(! str_contains($gallery, '@error='), 'Gallery Alpine error handler must not use Blade-reserved @error shorthand.');

$expect(str_contains($details, 'np-product-information'), 'Product information uses the prototype tabbed information card.');

$productDetailSources = $show.$builder.$gallery.$details.$signals.$stepper.$orderSummary;
foreach (['#e91d33', '#c9182b', '#15345d', '#0d2545', '#2467b7', '#061744', '#0b2450', '#2563eb', '#19b7d7', 'rgba(233, 29, 51'] as $legacyColor) {
    $expect(stripos($productDetailSources, $legacyColor) === false, "Product-detail sources do not use legacy brand color {$legacyColor}.");
}

$prototypeBlockPosition = strpos($css, 'NEXTPLAY_PRODUCT_DETAIL_PROTOTYPE');
$prototypeCss = $prototypeBlockPosition === false ? '' : substr($css, $prototypeBlockPosition);
$expect(str_contains($prototypeCss, 'var(--np-color-primary)'), 'Product-detail CSS uses centralized primary color.');
$expect(str_contains($prototypeCss, 'var(--np-color-secondary)'), 'Product-detail CSS uses centralized secondary color.');
$expect(str_contains($prototypeCss, 'var(--np-font-body)'), 'Product-detail CSS uses centralized body font.');
$expect(str_contains($prototypeCss, 'var(--np-font-heading)'), 'Product-detail CSS uses centralized heading font.');
$expect(! str_contains($prototypeCss, 'width: min(1500px'), 'Product-detail desktop layout must not override the centralized 1180px storefront container.');
$expect(str_contains($prototypeCss, 'grid-template-columns: minmax(0, 555px) minmax(0, 1fr)'), 'Desktop hero uses the prototype 555px gallery column.');
$expect(str_contains($prototypeCss, 'aspect-ratio: 277 / 263'), 'Desktop gallery stage matches the prototype landscape proportion instead of forcing a square.');
$expect(str_contains($prototypeCss, 'grid-template-columns: repeat(3, minmax(0, 104px))'), 'Material options keep the compact prototype card width.');
$expect(str_contains($prototypeCss, 'font-size: clamp(1.55rem, 2vw, 1.75rem)'), 'Product title uses the compact prototype scale.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail prototype regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail prototype regression passed.\n";
