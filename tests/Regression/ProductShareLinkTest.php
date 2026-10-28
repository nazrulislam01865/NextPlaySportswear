<?php

$root = dirname(__DIR__, 2);
$controller = (string) file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');
$layout = (string) file_get_contents($root.'/resources/views/components/layouts/storefront.blade.php');
$menu = (string) file_get_contents($root.'/resources/views/components/storefront/product/purchase-signals.blade.php');
$source = (string) file_get_contents($root.'/resources/js/storefront.js');
$manifest = json_decode((string) file_get_contents($root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$asset = $root.'/public/build/'.$manifest['resources/js/storefront.js']['file'];
$built = (string) file_get_contents($asset);

$check = static function (bool $ok, string $message): void {
    if (! $ok) {
        fwrite(STDERR, "Product sharing regression failed: {$message}\n");
        exit(1);
    }
};

$check(str_contains($controller, "route('products.show', ['slug' => \$product['slug']], false)"), 'share URL must use the actual product route');
$check(str_contains($controller, "'url' => \$productSharePath"), 'share data must not use SEO canonical override');
$check(str_contains($controller, "'og_url' => \$productShareUrl"), 'social preview URL must use the public product route');
$check(str_contains($layout, "\$seo['og_url'] ?? \$canonical"), 'Open Graph must support the share route');
foreach (['copyProductLink()', "shareThrough('whatsapp')", "shareThrough('facebook')", "shareThrough('x')", "shareThrough('email')", 'shareWithDevice()'] as $action) {
    $check(str_contains($menu, $action), 'missing share channel: '.$action);
}
$check(str_contains($source, 'this.shareOpen = !this.shareOpen'), 'Share must open site social choices');
$check(! str_contains($source, 'shareImageFile()'), 'native sharing must not attach image files');
$check(str_contains($source, 'await navigator.share(this.sharePayload())'), 'native share must only carry URL payload');
$check(is_file($asset), 'Vite manifest must point to an existing storefront asset');
$check(str_contains($built, 'nativeShareAvailable(){'), 'compiled script must include new native share menu');
$check(! str_contains($built, 'shareImageFile(){'), 'compiled script must not carry image sharing');
$check(str_contains($built, 'this.shareOpen=!this.shareOpen'), 'compiled script must show social menu');

echo "Product sharing regression passed.\n";
