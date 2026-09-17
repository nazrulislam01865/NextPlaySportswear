<?php

namespace Tests\Feature\Api\V1\Cart;

use App\Models\Coupon;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCouponApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $product = [
            'slug' => 'coupon-product', 'title' => 'Coupon Product', 'short_title' => 'Coupon Product',
            'summary' => '', 'sku' => 'CP-1', 'category' => 'Team', 'sport' => 'Team', 'image' => '/x.jpg', 'alt' => '',
            'url' => '/products/coupon-product', 'base_price' => 50, 'price' => 'From $50', 'minimum_quantity' => 1,
            'maximum_quantity' => 100, 'is_customizable' => false, 'track_inventory' => false, 'allow_backorder' => true,
            'product_profile' => 'standard', 'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 50]],
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

    public function test_valid_invalid_and_expired_coupon_behavior_matches_coupon_service(): void
    {
        Coupon::query()->create([
            'name' => 'API Team 10', 'code' => 'API10', 'discount_type' => 'percentage', 'discount_value' => 10,
            'minimum_subtotal' => 0, 'used_count' => 0, 'is_active' => true,
        ]);
        Coupon::query()->create([
            'name' => 'Expired', 'code' => 'OLD10', 'discount_type' => 'percentage', 'discount_value' => 10,
            'minimum_subtotal' => 0, 'used_count' => 0, 'is_active' => true, 'expires_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/cart/items', ['product_slug' => 'coupon-product', 'quantity' => 2])->assertCreated();

        $this->postJson('/api/v1/cart/coupon', ['coupon_code' => 'API10'])
            ->assertOk()
            ->assertJsonPath('data.coupon.code', 'API10');

        $this->deleteJson('/api/v1/cart/coupon')
            ->assertOk()
            ->assertJsonPath('data.coupon', null);

        $this->postJson('/api/v1/cart/coupon', ['coupon_code' => 'DOES-NOT-EXIST'])
            ->assertStatus(422)
            ->assertJsonPath('data.coupon', null);

        $this->postJson('/api/v1/cart/coupon', ['coupon_code' => 'OLD10'])
            ->assertStatus(422);
    }
}
