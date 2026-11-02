<?php
$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/storefront/content/shipping.blade.php');
$service = file_get_contents($root.'/app/Services/Storefront/ShippingDeliveryPageService.php');
foreach ([':seo="$seo"', 'np-shipping-page', 'np-shipping-breadcrumb', 'np-shipping-card', 'np-shipping-sidebar', 'np-shipping-content', 'np-shipping-table', 'np-track-btn', 'sections.08.title'] as $required) {
    if (! str_contains($view, $required)) { fwrite(STDERR, "Missing new shipping design: $required\n"); exit(1); }
}
foreach (['display_header', "'sections' => [", "'01' => [", "'08' => [", 'withNewShippingContent'] as $required) {
    if (! str_contains($service, $required)) { fwrite(STDERR, "Missing managed section: $required\n"); exit(1); }
}
foreach (['np-shipping-delivery-tabs', 'np-shipping-delivery-info-grid', 'Test Contract'] as $legacy) {
    if (str_contains($view, $legacy)) { fwrite(STDERR, "Legacy shipping view remains: $legacy\n"); exit(1); }
}
echo "New Shipping & Delivery storefront static check passed.\n";
