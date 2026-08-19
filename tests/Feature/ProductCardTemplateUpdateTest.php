<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductCardTemplateUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_product_card_uses_customizable_label_sku_and_discount_layout(): void
    {
        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => [
                'id' => 0,
                'slug' => 'custom-basketball-jersey',
                'title' => 'Custom Basketball Jersey',
                'summary' => 'Custom team jersey.',
                'sku' => 'NPS-BBJ-0023',
                'category' => 'Sports Uniforms',
                'price' => 'From $4.20',
                'base_price' => 4.75,
                'display_unit_price' => 4.20,
                'discount_price' => 4.20,
                'original_price' => 4.75,
                'original_price_label' => '$4.75',
                'discount_percentage' => 12,
                'currency' => 'USD',
                'is_customizable' => true,
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Custom Basketball Jersey',
                'url' => '#',
                'customization_options' => ['Custom design', 'Artwork upload'],
            ],
        ]);

        $this->assertStringContainsString('np-product-card-customizable', $html);
        $this->assertStringContainsString('Customizable', $html);
        $this->assertStringContainsString('SKU: NPS-BBJ-0023', $html);
        $this->assertStringContainsString('From $4.20', $html);
        $this->assertStringContainsString('$4.75', $html);
        $this->assertStringContainsString('12% OFF', $html);
        $this->assertStringNotContainsString('Custom design', $html);
        $this->assertStringNotContainsString('Artwork upload', $html);
    }

    public function test_listing_payload_treats_lower_admin_value_as_discount_price(): void
    {
        $product = Product::query()->create([
            'name' => 'Discounted Basketball Jersey',
            'slug' => 'discounted-basketball-jersey',
            'sku' => 'NPS-BBJ-DISCOUNT',
            'status' => 'active',
            'short_description' => 'Discounted team jersey.',
            'base_price' => 11.16,
            // The legacy column is intentionally used as the admin discount-price field.
            'compare_at_price' => 4.20,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $product->priceTiers()->create([
            'label' => '1-4',
            'minimum_quantity' => 1,
            'maximum_quantity' => 4,
            'unit_price' => 11.16,
            'sort_order' => 0,
        ]);
        $product->priceTiers()->create([
            'label' => '100+',
            'minimum_quantity' => 100,
            'maximum_quantity' => null,
            'unit_price' => 4.75,
            'sort_order' => 1,
        ]);

        $listing = app(ProductCatalogService::class)->latest(10);
        $card = collect($listing)->firstWhere('id', $product->id);

        $this->assertNotNull($card);
        $this->assertSame('From $4.20', $card['price']);
        $this->assertSame(4.20, $card['display_unit_price']);
        $this->assertSame(4.20, $card['discount_price']);
        $this->assertSame(4.75, $card['original_price']);
        $this->assertSame('$4.75', $card['original_price_label']);
        $this->assertSame(12, $card['discount_percentage']);
    }

    public function test_discount_is_hidden_when_entered_price_is_not_lower_than_original_price(): void
    {
        $product = Product::query()->create([
            'name' => 'Regular Basketball Jersey',
            'slug' => 'regular-basketball-jersey',
            'sku' => 'NPS-BBJ-REGULAR',
            'status' => 'active',
            'short_description' => 'Regular team jersey.',
            'base_price' => 4.75,
            'compare_at_price' => 5.25,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $product->priceTiers()->create([
            'label' => '1+',
            'minimum_quantity' => 1,
            'maximum_quantity' => null,
            'unit_price' => 4.75,
            'sort_order' => 0,
        ]);

        $listing = app(ProductCatalogService::class)->latest(10);
        $card = collect($listing)->firstWhere('id', $product->id);

        $this->assertNotNull($card);
        $this->assertSame('From $4.75', $card['price']);
        $this->assertSame(4.75, $card['display_unit_price']);
        $this->assertNull($card['discount_price']);
        $this->assertNull($card['original_price']);
        $this->assertNull($card['discount_percentage']);
    }
}
