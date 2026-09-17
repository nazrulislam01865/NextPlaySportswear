<?php

namespace Tests\Unit\Services\Storefront;

use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Catalog\NavigationService;
use App\Services\Storefront\StorefrontBootstrapService;
use App\Services\Wishlist\WishlistHeaderService;
use Mockery;
use Tests\TestCase;

class StorefrontBootstrapServiceTest extends TestCase
{
    public function test_it_aggregates_safe_customer_cart_wishlist_and_navigation_state(): void
    {
        config([
            'storefront.name' => 'NextPlay Sportswear',
            'storefront.tagline' => 'Custom team sportswear.',
            'storefront.logo' => '/images/logo.png',
        ]);

        $customer = new User([
            'name' => 'Customer One',
            'email' => 'customer@example.com',
        ]);
        $customer->setAttribute('id', 44);
        $customer->setAttribute('role', 'customer');
        $customer->setAttribute('is_active', true);
        $customer->setAttribute('email_verified_at', now());

        $cart = Mockery::mock(CartService::class);
        $cart->shouldReceive('headerSummary')->once()->with(4)->andReturn([
            'quantity' => 7,
            'total_items' => 3,
            'total' => 256.00,
        ]);

        $wishlist = Mockery::mock(WishlistHeaderService::class);
        $wishlist->shouldReceive('summary')->once()->with($customer, 4)->andReturn([
            'total_items' => 2,
        ]);

        $navigation = Mockery::mock(NavigationService::class);
        $navigation->shouldReceive('storefrontMenus')->once()->andReturn([]);

        $service = new StorefrontBootstrapService($cart, $wishlist, $navigation);
        $result = $service->get($customer);

        $this->assertSame('NextPlay Sportswear', $result['site']['name']);
        $this->assertSame(44, $result['customer']['id']);
        $this->assertSame('customer@example.com', $result['customer']['email']);
        $this->assertTrue($result['customer']['email_verified']);
        $this->assertSame(['quantity' => 7, 'total_items' => 3, 'total' => 256.0], $result['cart']);
        $this->assertSame(['total_items' => 2], $result['wishlist']);
        $this->assertArrayNotHasKey('role', $result['customer']);
        $this->assertArrayNotHasKey('password', $result['customer']);
    }

    public function test_it_treats_non_customer_accounts_as_guest_storefront_users(): void
    {
        $admin = new User([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->setAttribute('id', 1);
        $admin->setAttribute('role', 'admin');
        $admin->setAttribute('is_active', true);

        $cart = Mockery::mock(CartService::class);
        $cart->shouldReceive('headerSummary')->once()->with(4)->andReturn([
            'quantity' => 0,
            'total_items' => 0,
            'total' => 0.0,
        ]);

        $wishlist = Mockery::mock(WishlistHeaderService::class);
        $wishlist->shouldReceive('summary')->once()->with(null, 4)->andReturn([
            'total_items' => 0,
        ]);

        $navigation = Mockery::mock(NavigationService::class);
        $navigation->shouldReceive('storefrontMenus')->once()->andReturn([]);

        $service = new StorefrontBootstrapService($cart, $wishlist, $navigation);
        $result = $service->get($admin);

        $this->assertNull($result['customer']);
        $this->assertSame(['total_items' => 0], $result['wishlist']);
    }
}
