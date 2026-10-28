<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class CartProductCountDisplayTest extends TestCase
{
    public function test_cart_product_count_is_separate_from_total_unit_quantity(): void
    {
        $service = file_get_contents(app_path('Services/Cart/CartService.php'));

        $this->assertStringContainsString("'quantity' => \$quantity,", $service);
        $this->assertStringContainsString("'product_count' => count(\$items),", $service);
        $this->assertStringContainsString("'total_items' => \$totalItems,", $service);
        $this->assertStringContainsString("'quantity' => \$quantity,", $service);
    }

    public function test_header_uses_existing_cart_entry_count_not_quantity(): void
    {
        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $header = file_get_contents(resource_path('views/components/storefront/header.blade.php'));

        $this->assertStringContainsString("\$headerCart['total_items'] ?? 0", $provider);
        $this->assertStringContainsString("\$headerCart['total_items'] ?? 0", $header);
        $this->assertStringNotContainsString("\$headerCart['quantity'] ?? 0", $provider);
        $this->assertStringNotContainsString("\$headerCart['quantity'] ?? 0", $header);
    }

    public function test_cart_and_promo_refresh_show_cart_product_count(): void
    {
        $page = file_get_contents(resource_path('views/storefront/cart/index.blade.php'));
        $summary = file_get_contents(resource_path('views/components/storefront/cart/summary-card.blade.php'));
        $referral = file_get_contents(resource_path('views/storefront/referral/cart.blade.php'));

        $this->assertStringContainsString("\$cart['product_count']", $page);
        $this->assertStringContainsString('cart.product_count', $page);
        $this->assertStringContainsString("\$cart['product_count']", $summary);
        $this->assertStringContainsString('cart.product_count', $summary);
        $this->assertStringContainsString("\$cart['product_count']", $referral);
        $this->assertStringNotContainsString('data-cart-quantity', $page);
        $this->assertStringNotContainsString('data-cart-quantity', $summary);
    }
}
