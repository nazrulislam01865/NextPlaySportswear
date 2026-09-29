<?php

$root = dirname(__DIR__, 2);
$files = [
    'model' => $root.'/app/Models/ShippingDeliveryPageSetting.php',
    'service' => $root.'/app/Services/Storefront/ShippingDeliveryPageService.php',
    'request' => $root.'/app/Http/Requests/Admin/ShippingDeliveryPageRequest.php',
    'media' => $root.'/app/Services/Catalog/ShippingDeliveryPageMediaService.php',
    'controller' => $root.'/app/Http/Controllers/Admin/ShippingDeliveryPageController.php',
    'migration' => $root.'/database/migrations/2026_09_29_000200_create_shipping_delivery_page_settings_table.php',
];
foreach ($files as $label => $file) {
    if (! is_file($file)) {
        fwrite(STDERR, "Missing shipping delivery {$label}: {$file}\n");
        exit(1);
    }
}

$service = file_get_contents($files['service']);
foreach ([
    'ORDER INFORMATION', 'DELIVERY', 'Before You Order', 'Artwork & Customisation',
    'BEFORE YOU PAY', 'AFTER DISPATCH', 'HOW DELIVERY WORKS', 'DELIVERY QUESTIONS',
    'SHIPPING ADDRESS CHECKLIST', 'CHECK YOUR ORDER STATUS', '/track-order', '/terms-conditions',
    "'before-you-order'", "'artwork-customisation'", "'after-you-order'", "'delivery'", "'help'",
    'Schema::hasTable', 'PublicUrl::isAllowed', 'isSafeShippingDeliveryPath', 'mergeKnownShape',
] as $snippet) {
    if (! str_contains($service, $snippet)) {
        fwrite(STDERR, "Shipping delivery service missing: {$snippet}\n"); exit(1);
    }
}

$request = file_get_contents($files['request']);
foreach (['TAB_IDS', 'INFO_CARD_IDS', 'STEP_IDS', 'FAQ_IDS', 'CHECKLIST_IDS', 'PublicUrl::isAllowed', 'max:2048', 'validatedContent'] as $snippet) {
    if (! str_contains($request, $snippet)) { fwrite(STDERR, "Shipping delivery request missing: {$snippet}\n"); exit(1); }
}

$controller = file_get_contents($files['controller']);
foreach (['$this->media->prepare', 'DB::transaction', '$this->media->rollback', '$this->media->commitCleanup', 'withErrors'] as $snippet) {
    if (! str_contains($controller, $snippet)) { fwrite(STDERR, "Shipping delivery controller safety flow missing: {$snippet}\n"); exit(1); }
}

$media = file_get_contents($files['media']);
foreach (['shipping-delivery-page/info-cards/icons','shipping-delivery-page/notice/icons','shipping-delivery-page/address-checklist/icons','isShippingDeliveryOwnedPath','rollback','commitCleanup'] as $snippet) {
    if (! str_contains($media, $snippet)) { fwrite(STDERR, "Shipping delivery media missing: {$snippet}\n"); exit(1); }
}

$rbac = file_get_contents($root.'/app/Support/AdminRbac.php');
$routes = file_get_contents($root.'/routes/web.php');
$sidebar = file_get_contents($root.'/resources/views/components/layouts/admin.blade.php');
foreach (['shipping_delivery_page.view','shipping_delivery_page.manage','shipping-delivery-page.'] as $snippet) {
    if (! str_contains($rbac, $snippet)) { fwrite(STDERR, "RBAC missing: {$snippet}\n"); exit(1); }
}
foreach (['admin.shipping-delivery-page', '/shipping-delivery-page'] as $snippet) {
    if (! str_contains($routes, 'shipping-delivery-page')) { fwrite(STDERR, "Admin routes missing shipping-delivery-page.\n"); exit(1); }
}
if (! str_contains($sidebar, 'Shipping & Delivery Page')) { fwrite(STDERR, "Sidebar missing Shipping & Delivery Page.\n"); exit(1); }

echo "Shipping & Delivery backend static check passed.\n";
