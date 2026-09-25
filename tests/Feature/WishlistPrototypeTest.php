<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class WishlistPrototypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlist_card_is_reusable_and_matches_the_approved_card_structure(): void
    {
        $html = Blade::render('<x-storefront.wishlist-card :item="$item" />', [
            'item' => [
                'id' => 42,
                'title' => 'Custom Basketball Jersey',
                'url' => '/products/custom-basketball-jersey',
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Custom Basketball Jersey',
                'category' => 'Basketball Jerseys',
                'price' => 5.07,
                'price_available' => true,
                'currency' => 'USD',
                'saved_at' => now()->toIso8601String(),
                'remove_endpoint' => '/wishlist/products/42',
            ],
        ]);

        $this->assertStringContainsString('np-product-card--canonical np-wishlist-card', $html);
        $this->assertStringContainsString('np-product-square-media np-wishlist-card-media', $html);
        $this->assertStringContainsString('np-product-square-image np-wishlist-card-image', $html);
        $this->assertStringContainsString('np-product-card-body np-wishlist-card-body', $html);
        $this->assertStringContainsString('width="900"', $html);
        $this->assertStringContainsString('height="900"', $html);
        $this->assertStringContainsString('Basketball Jerseys', $html);
        $this->assertStringContainsString('Custom Basketball Jersey', $html);
        $this->assertStringContainsString('$5.07', $html);
        $this->assertStringContainsString('View Product', $html);
        $this->assertStringContainsString('Remove', $html);
        $this->assertStringContainsString('btn btn-navy', $html);
        $this->assertStringContainsString('btn btn-white', $html);
        $this->assertStringNotContainsString('Minimum', $html);
        $this->assertStringNotContainsString('Saved for later', $html);
    }

    public function test_wishlist_page_uses_the_reusable_card_and_prototype_controls(): void
    {
        $view = file_get_contents(resource_path('views/storefront/wishlist/index.blade.php'));

        $this->assertIsString($view);
        $this->assertGreaterThanOrEqual(2, substr_count($view, '<x-storefront.wishlist-card'));
        $this->assertStringContainsString('data-wishlist-sort', $view);
        $this->assertStringContainsString('np-wishlist-grid np-product-listing-grid np-product-listing-grid--three', $view);
        $this->assertStringContainsString('Sort: Recently saved', $view);
        $this->assertStringContainsString('Sign in to keep your wishlist across devices.', $view);
        $this->assertStringContainsString('Continue Shopping', $view);
        $this->assertStringNotContainsString('Build Your Team Order', $view);
        $this->assertStringNotContainsString('np-wishlist-breadcrumb', $view);
    }


    public function test_wishlist_presentation_reuses_product_card_scale_without_rounded_card_corners(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('NEXTPLAY_WISHLIST_PROTOTYPE', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/\.storefront-clean-ui \.np-wishlist-card\s*\{[^}]*border-radius:/s',
            $css
        );
        $this->assertStringContainsString('border-radius: 0;', strstr($css, 'NEXTPLAY_CENTRALIZED_PRODUCT_CARD'));
    }

    public function test_guest_wishlist_product_resolution_uses_the_centralized_catalog_price(): void
    {
        $product = Product::query()->create([
            'name' => 'Wishlist Price Tier Jersey',
            'slug' => 'wishlist-price-tier-jersey',
            'sku' => 'WISH-PRICE-001',
            'status' => 'active',
            'short_description' => 'Wishlist pricing test product.',
            'base_price' => 11.16,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $product->priceTiers()->create([
            'label' => '1-49',
            'minimum_quantity' => 1,
            'maximum_quantity' => 49,
            'unit_price' => 11.16,
            'sort_order' => 0,
        ]);
        $product->priceTiers()->create([
            'label' => '100+',
            'minimum_quantity' => 100,
            'maximum_quantity' => null,
            'unit_price' => 5.07,
            'sort_order' => 1,
        ]);

        $this->postJson(route('wishlist.guest-products'), [
            'product_ids' => [$product->id],
        ])->assertOk()
            ->assertJsonPath('products.'.$product->id.'.price', 5.07)
            ->assertJsonPath('products.'.$product->id.'.price_available', true)
            ->assertJsonPath('products.'.$product->id.'.title', 'Wishlist Price Tier Jersey');
    }
}
