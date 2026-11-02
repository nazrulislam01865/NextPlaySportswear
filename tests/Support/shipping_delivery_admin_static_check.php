<?php
$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/admin/shipping-delivery-page/edit.blade.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/ShippingDeliveryPageRequest.php');
$controller = file_get_contents($root.'/app/Http/Controllers/Admin/ShippingDeliveryPageController.php');
foreach (['display_header[title]', 'display_header[subtitle]', 'sections[01][title]', 'sections[02][formula]', 'sections[03][options]', 'sections[06][steps]', 'sections[08][contact_url]', 'seo[title]', 'seo[description]', "route('shipping')"] as $snippet) {
    if (! str_contains($view, $snippet)) { fwrite(STDERR, "New admin editor missing: $snippet\n"); exit(1); }
}
if (! str_contains($request, 'PublicUrl::isAllowed') || ! str_contains($controller, "['page_key' => 'shipping-delivery']")) {
    fwrite(STDERR, "New shipping editor validation or persistence missing.\n"); exit(1);
}
echo "Shipping & Delivery eight-section admin editor static check passed.\n";
