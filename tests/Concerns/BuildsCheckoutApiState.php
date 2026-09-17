<?php

namespace Tests\Concerns;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;

trait BuildsCheckoutApiState
{
    protected function checkoutCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'auth_session_version' => 0,
            'phone' => '+15555550123',
            'name' => 'Checkout Customer',
        ], $overrides));
    }

    protected function authenticateCheckoutCustomer(User $customer): void
    {
        $this->actingAs($customer, 'web');
        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => (int) $customer->auth_session_version]);
    }

    protected function bindCheckoutCatalog(): void
    {
        $product = [
            'slug' => 'checkout-api-product', 'title' => 'Checkout API Product', 'short_title' => 'Checkout API Product',
            'summary' => '', 'sku' => 'CHECKOUT-API-1', 'category' => 'Team', 'sport' => 'Team', 'image' => '/checkout.jpg',
            'alt' => '', 'url' => '/products/checkout-api-product', 'base_price' => 10, 'price' => 'From $10',
            'minimum_quantity' => 1, 'maximum_quantity' => 100, 'is_customizable' => false,
            'track_inventory' => false, 'allow_backorder' => true, 'product_profile' => 'standard',
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 10]],
            'option_groups' => [], 'size_groups' => [], 'production_speeds' => [], 'shipping_methods' => [],
            'roster' => ['enabled' => false, 'optional' => true, 'fields' => []],
            'artwork_upload' => ['enabled' => false, 'required' => false, 'max_files' => 5],
        ];

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array { return $slug === $this->fixture['slug'] ? $this->fixture : null; }
            public function findFullBySlug(string $slug): ?array { return $this->findBySlug($slug); }
        };

        $this->app->instance(ProductCatalogService::class, $catalog);
    }

    protected function addCheckoutItem(int $quantity = 2): void
    {
        $this->postJson('/api/v1/cart/items', [
            'product_slug' => 'checkout-api-product',
            'quantity' => $quantity,
        ])->assertCreated();
    }

    protected function completeInformation(): void
    {
        $this->putJson('/api/v1/checkout/information', [
            'contact_choice' => 'saved',
        ])->assertOk();
    }

    protected function completeShipping(array $overrides = []): void
    {
        $this->putJson('/api/v1/checkout/shipping-address', array_merge([
            'address_choice' => 'new',
            'first_name' => 'Checkout',
            'last_name' => 'Customer',
            'address_line_1' => '100 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'country' => 'United States',
            'postal_code' => '78701',
            'phone' => '+15555550123',
        ], $overrides))->assertOk();
    }

    protected function completeBilling(): void
    {
        $this->putJson('/api/v1/checkout/billing-address', ['same_as_shipping' => true])->assertOk();
    }

    protected function completePayment(): void
    {
        $this->putJson('/api/v1/checkout/payment-method', ['payment_method' => 'invoice'])->assertOk();
    }
}
