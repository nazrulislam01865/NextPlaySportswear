<?php

$root = dirname(__DIR__, 2);
$read = static function (string $path) use ($root): string {
    $source = file_get_contents($root.'/'.$path);
    if ($source === false) {
        throw new RuntimeException('Missing source: '.$path);
    }
    return $source;
};
$cartItem = $read('resources/views/components/storefront/cart/item-card.blade.php');
$productController = $read('app/Http/Controllers/Storefront/ProductController.php');
$builder = $read('resources/views/components/storefront/product/builder.blade.php');
$cartController = $read('app/Http/Controllers/Storefront/CartController.php');
$cartService = $read('app/Services/Cart/CartService.php');
$checkout = $read('app/Services/Checkout/CheckoutService.php');
$js = $read('resources/js/storefront.js');
$manifest = json_decode($read('public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$builtFile = $manifest['resources/js/storefront.js']['file'] ?? '';
$built = $read('public/build/'.$builtFile);

$assert = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException('Cart editing regression failed: '.$message);
    }
};
$assert(str_contains($cartItem, "['slug' => \$item['product_slug'], 'cart_item' => \$item['key']]"), 'Edit targets the specific saved cart item');
$assert(str_contains($cartItem, '#configure-product'), 'Edit opens customization controls');
$assert(str_contains($cartItem, '>Edit</a>'), 'Edit is available on the cart card');
$assert(str_contains($cartItem, "route('cart.items.destroy'"), 'Remove action is preserved');
$assert(str_contains($productController, '$this->cart->findItem($cartItemKey)'), 'The product editor fetches the current cart only');
$assert(str_contains($productController, 'hash_equals('), 'Editor checks product matches the cart item');
$assert(str_contains($builder, "route('cart.items.options.update', \$editItem['key'])"), 'Editor submits update instead of adding a duplicate');
$assert(str_contains($builder, "'roster_same_for_all' => (bool) (\$editConfiguration['roster_same_for_all'] ?? false)"), 'Editor restores shared personalization mode');
$assert(str_contains($js, 'this.rosterSameForAll = Boolean(initial.roster_same_for_all)'), 'Source JS restores shared personalization flag');
$assert(str_contains($built, 'this.rosterSameForAll=!!t.roster_same_for_all'), 'Bundled JS contains shared personalization fix');
$assert(str_contains($cartController, '$this->cart->replace($cartItem, $payload)'), 'The edit endpoint replaces existing cart data');
$assert(str_contains($cartController, "Storage::disk('local')->delete(array_merge(\$artwork['new_paths'], \$personalization['new_paths']))"), 'Failed edits clear newly staged uploads');
$assert(str_contains($cartService, '$this->repriceItem(['), 'Saved edited configuration is recalculated');
$assert(str_contains($cartService, 'Another cart item already uses these exact options'), 'Duplicate edits are guarded');
$assert(str_contains($checkout, "'customization' => \$customization"), 'Checkout persists edited customization to order item');
$assert(str_contains($checkout, "'quantity' => (int) (\$cartItem['quantity'] ?? 1)"), 'Checkout persists revised quantity');
$assert(str_contains($checkout, "'line_total' => (float) (\$cartItem['line_total'] ?? 0)"), 'Checkout persists revised total');
echo "Cart item editing flow regression passed.\n";
