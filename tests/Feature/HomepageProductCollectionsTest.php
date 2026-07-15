<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomepageProductCollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_homepage_includes_featured_latest_and_best_selling_product_collections(): void
    {
        $this->createProduct('Featured Product', 'featured-product', now()->subDay(), true);
        $this->createProduct('Newest Product', 'newest-product', now(), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Featured Products')
            ->assertSee('Latest')
            ->assertSee('Best Selling')
            ->assertSee('home-product-mobile-list', false)
            ->assertSee('home-product-mobile-item', false);
    }

    public function test_latest_products_are_ordered_by_publication_date(): void
    {
        $older = $this->createProduct('Older Product', 'older-product', now()->subDays(4));
        $newer = $this->createProduct('Newer Product', 'newer-product', now()->subDay());

        $latest = app(ProductCatalogService::class)->latest(2);

        $this->assertSame($newer->id, $latest[0]['id']);
        $this->assertSame($older->id, $latest[1]['id']);
    }

    public function test_best_selling_products_use_net_paid_order_quantity(): void
    {
        $topSeller = $this->createProduct('Top Seller', 'top-seller', now()->subDays(3));
        $secondSeller = $this->createProduct('Second Seller', 'second-seller', now()->subDays(2));
        $fallback = $this->createProduct('Fallback Product', 'fallback-product', now()->subDay());

        $paidOrder = Order::query()->create([
            'order_number' => 'NP-TEST-1001',
            'status' => 'completed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'USD',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'total_quantity' => 10,
            'placed_at' => now(),
        ]);

        $this->createOrderItem($paidOrder, $topSeller, 8, 1, 1);
        $this->createOrderItem($paidOrder, $secondSeller, 5, 1, 0);

        $cancelledOrder = Order::query()->create([
            'order_number' => 'NP-TEST-1002',
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Cancelled Customer',
            'customer_email' => 'cancelled@example.com',
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'total_quantity' => 20,
            'placed_at' => now(),
        ]);

        $this->createOrderItem($cancelledOrder, $fallback, 20, 0, 0);

        $bestSelling = app(ProductCatalogService::class)->bestSelling(3);

        $this->assertSame($topSeller->id, $bestSelling[0]['id']);
        $this->assertSame($secondSeller->id, $bestSelling[1]['id']);
        $this->assertSame($fallback->id, $bestSelling[2]['id']);
    }

    private function createProduct(
        string $name,
        string $slug,
        $publishedAt,
        bool $featured = false,
    ): Product {
        return Product::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper(str_replace('-', '_', $slug)),
            'status' => 'active',
            'short_description' => $name.' description',
            'base_price' => 25,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_featured' => $featured,
            'is_customizable' => true,
            'is_active' => true,
            'published_at' => $publishedAt,
        ]);
    }

    private function createOrderItem(
        Order $order,
        Product $product,
        int $quantity,
        int $cancelledQuantity,
        int $returnedQuantity,
    ): OrderItem {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $quantity,
            'fulfilled_quantity' => $quantity - $cancelledQuantity,
            'cancelled_quantity' => $cancelledQuantity,
            'returned_quantity' => $returnedQuantity,
            'unit_price' => 10,
            'customization_unit_price' => 0,
            'line_total' => $quantity * 10,
            'is_digital' => false,
        ]);
    }
}
