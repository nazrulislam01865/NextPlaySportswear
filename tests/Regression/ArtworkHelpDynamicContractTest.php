<?php

// Lightweight source-level regression test: runnable without a database or vendor tree.
$root = dirname(__DIR__, 2);
$sources = [
    'migration' => file_get_contents($root.'/database/migrations/2026_10_19_000001_create_artwork_preferences_tables.php'),
    'admin' => file_get_contents($root.'/app/Http/Controllers/Admin/ArtworkPreferenceController.php'),
    'product' => file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php'),
    'product_form' => file_get_contents($root.'/resources/views/admin/products/_form.blade.php'),
    'sidebar' => file_get_contents($root.'/resources/views/components/layouts/admin.blade.php'),
    'route' => file_get_contents($root.'/routes/web.php'),
    'catalog' => file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php'),
    'builder' => file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php'),
    'frontend' => file_get_contents($root.'/resources/js/storefront.js'),
    'request' => file_get_contents($root.'/app/Http/Requests/Storefront/AddCartItemRequest.php'),
    'cart' => file_get_contents($root.'/app/Http/Controllers/Storefront/CartController.php'),
    'cart_service' => file_get_contents($root.'/app/Services/Cart/CartService.php'),
    'orders' => file_get_contents($root.'/app/Models/OrderItem.php'),
    'flowtrack' => file_get_contents($root.'/app/Services/Integrations/FlowTrack/FlowTrackOrderPayloadFactory.php'),
];
$checks = [
    ['migration', "Schema::create('artwork_preferences'", 'Master data migration exists'],
    ['migration', "Schema::create('artwork_preference_product'", 'Product assignments are pivoted'],
    ['admin', 'assertType($type)', 'Only known preference types accepted'],
    ['product', 'artworkPreferences()->sync', 'Product assignments persist'],
    ['route', "->name('artwork-preferences.index')", 'Artwork master routes registered'],
    ['sidebar', '>Pantone Colors</x-admin.sidebar-sub-link>', 'Pantone master menu is visible'],
    ['sidebar', '>Design Styles</x-admin.sidebar-sub-link>', 'Style master menu is visible'],
    ['product_form', 'id="artwork-preferences"', 'Product form contains artwork preferences'],
    ['product_form', 'name="artwork_preference_ids[]"', 'Product checkboxes post assignments'],
    ['catalog', "'artwork_preferences'", 'Assigned options reach storefront'],
    ['builder', 'config.artwork_preferences?.pantone', 'Pantone selector is dynamic'],
    ['builder', 'config.artwork_preferences?.style', 'Style selector is dynamic'],
    ['builder', "artwork_reference_files[]", 'Help reference input is distinct'],
    ['builder', "product-placeholder.svg", 'Product inspiration omits placeholder'],
    ['frontend', "artwork_help_color_id", 'Pantone ID goes into cart config'],
    ['frontend', "artwork_help_style_id", 'Style ID goes into cart config'],
    ['frontend', "purpose === 'reference'", 'Reference images have own upload rules'],
    ['frontend', "dropArtworkFiles(event, purpose", 'Reference images support dropping files'],
    ['request', 'artwork_reference_files.*', 'References validated by Laravel'],
    ['cart', 'validateArtworkHelp($payload, $product)', 'Server checks help details before uploads'],
    ['cart', '\'purpose\' => $purpose', 'Purpose tag persists with files'],
    ['cart_service', "'artwork_help' => [", 'Help snapshot normalized server-side'],
    ['orders', "'purpose' =>", 'Order items expose reference labels'],
    ['flowtrack', "'purpose' =>", 'Integration forwards reference labels'],
    ['flowtrack', "'artwork_help' =>", 'Integration forwards help choices'],
];
$errors = [];
foreach ($checks as [$source, $needle, $description]) {
    if (! str_contains((string) $sources[$source], $needle)) {
        $errors[] = $description;
    }
}
// The product preference panel must come after the Personalization step anchor,
// not before it where the stepper would skip the panel.
$productForm = $sources['product_form'];
if (strpos($productForm, 'id="artwork"') >= strpos($productForm, 'id="artwork-preferences"')) {
    $errors[] = 'Product artwork options are reachable via the Personalization step';
}
// These master links must be ahead of the long customization menu.
$sidebar = $sources['sidebar'];
if (strpos($sidebar, '>Pantone Colors</x-admin.sidebar-sub-link>') >= strpos($sidebar, '@foreach($primaryCustomizationMenuGroups')) {
    $errors[] = 'Artwork master links must appear first in Master Data';
}
if ($errors) {
    fwrite(STDERR, "Artwork help regression failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}
echo "Artwork help regression: ".count($checks)." checks passed.\n";
