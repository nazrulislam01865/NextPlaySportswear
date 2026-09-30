<?php

$root = dirname(__DIR__, 2);
$builderPath = $root.'/resources/views/components/storefront/product/builder.blade.php';
$cssPath = $root.'/resources/css/storefront.css';
$componentPaths = [
    'stepper' => $root.'/resources/views/components/storefront/product/customizer/stepper.blade.php',
    'step-header' => $root.'/resources/views/components/storefront/product/customizer/step-header.blade.php',
    'navigation' => $root.'/resources/views/components/storefront/product/customizer/navigation.blade.php',
    'order-summary' => $root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php',
    'price-table' => $root.'/resources/views/components/storefront/product/customizer/price-table.blade.php',
];

$builder = file_get_contents($builderPath);
$css = file_get_contents($cssPath);

if ($builder === false || $css === false) {
    fwrite(STDERR, "Unable to read product detail sources.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

foreach ($componentPaths as $name => $path) {
    $expect(is_file($path), "Reusable customizer component {$name} exists.");
}

$expect(! str_contains($builder, '<x-storefront.product.customizer.stepper'), 'Customizer uses the stacked accordion steps without a second horizontal stepper.');
$expect(str_contains($builder, '<x-storefront.product.customizer.order-summary'), 'Builder uses one reusable custom-order sidebar.');
$expect(str_contains($builder, '<x-storefront.product.customizer.price-table'), 'Price & Fabric step uses the prototype price-table component.');
$expect(substr_count($builder, '<x-storefront.product.customizer.navigation') >= 5, 'Customizer steps reuse one navigation component.');
$expect(substr_count($builder, '<x-storefront.product.customizer.step-header') >= 6, 'All six steps reuse one step-header component.');

foreach (range(1, 6) as $step) {
    $expect(str_contains($builder, 'x-show="activeCustomizerStep === '.$step.'"'), "Step {$step} has an explicit active-step panel.");
}

$expect(str_contains($builder, "artworkMode: 'upload'"), 'Artwork step has one local tab state without a new backend subsystem.');
$expect(str_contains($builder, "setArtworkMode(mode)"), 'Artwork mode tabs switch inside the existing Alpine builder state.');
$expect(str_contains($builder, "artworkHelpColor: ''"), 'Artwork help keeps the prototype color-preference state locally without adding a backend subsystem.');
$expect(str_contains($builder, 'Color Preference'), 'Artwork help includes the prototype Color Preference control.');
$expect(str_contains($builder, "artworkMode === 'help' ? ` | Design style:"), 'Artwork help metadata is only persisted when the help mode is active.');
$expect(str_contains($builder, ':download="file.name"'), 'Uploaded artwork rows include the prototype download action.');
$expect(str_contains($builder, 'Upload New Artwork'), 'Artwork prototype includes Upload New Artwork.');
$expect(str_contains($builder, 'Use Existing Design'), 'Artwork prototype includes Use Existing Design.');
$expect(str_contains($builder, 'Need Help with Artwork?'), 'Artwork prototype includes Need Help with Artwork.');
$expect(str_contains($builder, 'Production Lead Time'), 'Production step uses the prototype Production Lead Time heading.');
$expect(str_contains($builder, 'Shipping Method'), 'Production step uses the prototype Shipping Method heading.');
$expect(str_contains($builder, 'Choose your shipping method for this order.'), 'Shipping-only products use the exact shipping-only prototype description.');
$expect(str_contains($builder, 'Estimated Delivery'), 'Production step includes the estimated-delivery presentation.');
$expect(str_contains($builder, 'Important Notes'), 'Production step includes the prototype notes panel.');
$expect(str_contains($builder, ':title="$customizerSteps[2][\'title\']"'), 'Size step uses the centralized accordion step title.');
$expect(str_contains($builder, 'Sample Image'), 'Size step includes the prototype sample-image table column.');
$expect(str_contains($builder, 'Clear All'), 'Roster step includes the prototype clear-all action.');
$expect(str_contains($builder, 'Preview'), 'Roster step includes the prototype preview column.');
$expect(str_contains($builder, 'Review & Add to Cart'), 'Final review step keeps the exact prototype label.');
$expect(str_contains(file_get_contents($componentPaths['step-header']), 'In Progress'), 'Reusable accordion step header exposes the prototype in-progress state.');
$expect(str_contains(file_get_contents($componentPaths['order-summary']), 'Players to Add'), 'Step 3 sidebar exposes the prototype Players to Add fact.');
$expect(str_contains(file_get_contents($componentPaths['order-summary']), "artworkMode === 'help'"), 'Player summary appears on artwork help/review states exactly where the prototypes show it.');
$expect(str_contains(file_get_contents($componentPaths['order-summary']), 'Estimated Delivery'), 'Step 5 sidebar exposes the prototype Estimated Delivery summary.');
$expect(str_contains(file_get_contents($componentPaths['order-summary']), 'np-custom-order-method-lines'), 'Production and shipping sidebar uses the prototype two-line method summary.');
$expect(str_contains(file_get_contents($componentPaths['order-summary']), 'activeCustomizerStep === 1'), 'Order sidebar has explicit step-specific content instead of one generic fact list.');

$prototypePos = strpos($css, 'NEXTPLAY_PRODUCT_DETAIL_PROTOTYPE');
$prototypeCss = $prototypePos === false ? '' : substr($css, $prototypePos);
$expect(str_contains($prototypeCss, 'grid-template-columns: minmax(0, 1fr) 300px'), 'Desktop customizer matches the prototype main/sidebar proportion.');
$expect(str_contains($prototypeCss, 'gap: 28px'), 'Desktop customizer matches the prototype column gap.');
$expect(str_contains($prototypeCss, '.np-proto-step-card'), 'Prototype step cards have a dedicated reusable visual contract.');
$expect(str_contains($prototypeCss, '.np-proto-review-status'), 'Review in-progress state has a dedicated prototype style.');
$expect(str_contains($prototypeCss, '.np-proto-data-table'), 'Prototype table styling is centralized.');
$expect(str_contains($prototypeCss, '.np-proto-price-table'), 'Price table wrapper has an explicit prototype layout contract.');
$expect(str_contains($prototypeCss, '.np-proto-roster-table-wrap'), 'Roster table wrapper has an explicit prototype border/overflow contract.');
$expect(str_contains($prototypeCss, '.np-proto-artwork-tabs'), 'Artwork tabs use centralized prototype styling.');
$expect(str_contains($prototypeCss, '.np-proto-color-picker'), 'Artwork help color preferences use a dedicated prototype style.');
$expect(str_contains($prototypeCss, '76px 32px 32px 32px'), 'Artwork file rows allocate separate preview, download, and delete action columns.');
$expect(str_contains($prototypeCss, '.np-proto-choice-card'), 'Production/shipping choices use one reusable card style.');
$expect(str_contains($prototypeCss, '.np-custom-order-delivery'), 'Step 5 estimated-delivery sidebar row has a dedicated prototype style.');
$expect(str_contains($prototypeCss, '.np-custom-order-method-lines'), 'Production/shipping method lines have a dedicated prototype style.');
$expect(str_contains($prototypeCss, 'var(--np-color-primary)'), 'Full redesign continues using the centralized primary theme token.');
$expect(str_contains($prototypeCss, 'var(--np-color-secondary)'), 'Full redesign continues using the centralized secondary theme token.');

if ($failures !== []) {
    fwrite(STDERR, "Full product-detail prototype contract failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Full product-detail prototype contract passed.\n";
