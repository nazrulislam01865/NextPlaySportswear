<?php
$root = dirname(__DIR__, 2);
$viewFile = $root.'/resources/views/storefront/content/shipping.blade.php';
if (! is_file($viewFile)) { fwrite(STDERR, "Shipping view missing.\n"); exit(1); }
$view = file_get_contents($viewFile);
$service = file_get_contents($root.'/app/Services/Storefront/ShippingDeliveryPageService.php');
foreach ([
    ':seo="$seo"', 'np-shipping-delivery-page', '<x-storefront.shipping-delivery.tab-bar',
    '<x-storefront.shipping-delivery.info-card', '<x-storefront.shipping-delivery.timeline-step',
    '<x-storefront.shipping-delivery.notice', '<x-storefront.shipping-delivery.faq-row',
    '<x-storefront.shipping-delivery.checklist-row', "data_get(\$shippingDelivery, 'cta.primary_label')",
] as $snippet) {
    if (! str_contains($view, $snippet)) { fwrite(STDERR, "Shipping storefront missing: {$snippet}\n"); exit(1); }
}
foreach (['before-you-order','artwork-customisation','after-you-order','delivery','help'] as $id) {
    if (! str_contains($service, "'{$id}'")) { fwrite(STDERR, "Shipping service missing tab id {$id}.\n"); exit(1); }
}
if (str_contains($service, "delivery_steps.steps.0.icon_path")) { fwrite(STDERR, "Timeline steps must not gain uploaded icons.\n"); exit(1); }

$iconComponent = file_get_contents($root.'/resources/views/components/storefront/shipping-delivery/icon.blade.php');
foreach (['role="img"', 'aria-label="{{ $alt }}"'] as $snippet) {
    if (! str_contains($iconComponent, $snippet)) { fwrite(STDERR, "Shipping fallback icon accessibility missing: {$snippet}\n"); exit(1); }
}
echo "Shipping & Delivery storefront static check passed.\n";
