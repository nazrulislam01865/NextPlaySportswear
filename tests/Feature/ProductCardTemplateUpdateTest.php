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

    public function test_product_card_uses_the_centralized_structure_without_page_specific_actions_or_customizable_badge(): void
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
                'tag' => 'NEW',
                'tag_color' => 'blue',
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Custom Basketball Jersey',
                'url' => '#',
                'customization_options' => ['Custom design', 'Artwork upload'],
            ],
        ]);

        $this->assertStringContainsString('np-product-card--canonical', $html);
        $this->assertStringContainsString('np-product-card-badge', $html);
        $this->assertStringContainsString('NEW', $html);
        $this->assertStringNotContainsString('np-product-card-customizable', $html);
        $this->assertStringNotContainsString('Customizable', $html);
        $this->assertStringNotContainsString('np-product-card-details-link', $html);
        $this->assertStringNotContainsString('View product details', $html);
        $this->assertStringContainsString('Customize &amp; Order', $html);
        $this->assertStringContainsString('SKU: NPS-BBJ-0023', $html);
        $this->assertStringContainsString('From $4.20', $html);
        $this->assertStringContainsString('$4.75', $html);
        $this->assertStringContainsString('12% OFF', $html);
        $this->assertStringNotContainsString('Custom design', $html);
        $this->assertStringNotContainsString('Artwork upload', $html);
    }

    public function test_product_card_renders_an_explicit_customizable_gallery_badge(): void
    {
        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => [
                'id' => 0,
                'slug' => 'custom-baseball-jersey',
                'title' => 'Custom Baseball Jersey',
                'summary' => 'Custom team jersey.',
                'sku' => 'NPS-BSB-017',
                'category' => 'Baseball Jerseys',
                'price' => 'From $12.00',
                'base_price' => 12.00,
                'display_unit_price' => 12.00,
                'currency' => 'USD',
                'is_customizable' => true,
                'tag' => 'Customizable',
                'tag_color' => 'blue',
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Custom Baseball Jersey',
                'url' => '#',
            ],
        ]);

        $this->assertStringContainsString('np-product-card-badge', $html);
        $this->assertStringContainsString('np-product-card-badge--customizable', $html);
        $this->assertStringContainsString('Customizable', $html);
    }

    public function test_customizable_badge_uses_compact_square_off_white_orange_and_title_aligned_styling(): void
    {
        $themeCss = file_get_contents(resource_path('css/storefront-theme.css'));
        $css = file_get_contents(resource_path('css/storefront.css'));

        $this->assertStringContainsString('--np-color-orange: #CF5D38;', $themeCss);
        $this->assertStringContainsString('.np-product-card-badge--customizable {', $css);
        $this->assertStringContainsString('min-height: 1.6rem;', $css);
        $this->assertStringContainsString('padding: .34rem .58rem;', $css);
        $this->assertStringContainsString('border-radius: 0;', $css);
        $this->assertStringContainsString('background: var(--np-color-soft);', $css);
        $this->assertStringContainsString('left: 1.42rem;', $css);
        $this->assertStringContainsString('left: 1.08rem;', $css);
        $this->assertStringContainsString('left: 1.05rem;', $css);
        $this->assertStringContainsString('color: var(--np-color-orange);', $css);
        $this->assertStringContainsString('box-shadow: none;', $css);
    }

    public function test_compiled_storefront_asset_contains_current_customizable_badge_style(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $asset = public_path('build/'.$manifest['resources/css/storefront.css']['file']);
        $compiledCss = file_get_contents($asset);

        $this->assertStringContainsString('.np-product-card-badge--customizable', $compiledCss);
        $this->assertStringContainsString('background:var(--np-color-soft)', $compiledCss);
        $this->assertStringContainsString('color:var(--np-color-orange)', $compiledCss);
        $this->assertStringContainsString('border-radius:0', $compiledCss);
    }

    public function test_legacy_single_letter_customizable_badge_uses_the_customizable_style_until_cleanup_runs(): void
    {
        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => [
                'id' => 0,
                'title' => 'Legacy Customizable Product',
                'sku' => 'LEGACY-C',
                'category' => 'Legacy',
                'display_unit_price' => 10.00,
                'currency' => 'USD',
                'is_customizable' => true,
                'tag' => 'C',
                'tag_color' => 'red',
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Legacy Customizable Product',
                'url' => '#',
            ],
        ]);

        $this->assertStringContainsString('np-product-card-badge--customizable', $html);
        $this->assertStringContainsString('>
                C
            </span>', $html);
    }

    public function test_product_card_does_not_invent_a_badge_for_a_customizable_product(): void
    {
        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => [
                'id' => 0,
                'slug' => 'custom-baseball-jersey',
                'title' => 'Custom Baseball Jersey',
                'summary' => 'Custom team jersey.',
                'sku' => 'NPS-BSB-017',
                'category' => 'Baseball Jerseys',
                'price' => 'From $12.00',
                'base_price' => 12.00,
                'display_unit_price' => 12.00,
                'currency' => 'USD',
                'is_customizable' => true,
                'tag' => null,
                'tag_color' => 'blue',
                'image' => '/images/product-placeholder.svg',
                'alt' => 'Custom Baseball Jersey',
                'url' => '#',
            ],
        ]);

        $this->assertStringNotContainsString('np-product-card-badge', $html);
        $this->assertStringNotContainsString('Customizable', $html);
    }

    public function test_product_card_css_has_one_global_source_of_truth(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));
        $marker = 'NEXTPLAY_CENTRALIZED_PRODUCT_CARD';

        $this->assertSame(1, substr_count($css, $marker));

        $beforeCentralizedBlock = strstr($css, '/* '.$marker, true);
        $this->assertIsString($beforeCentralizedBlock);
        $this->assertStringNotContainsString('np-product-card', $beforeCentralizedBlock);

        $centralizedBlock = strstr($css, '/* '.$marker);
        $this->assertStringContainsString('border-radius: 0;', $centralizedBlock);
        $this->assertStringContainsString('padding: 0;', $centralizedBlock);
        $this->assertStringContainsString('border-radius: .4rem;', $centralizedBlock);
        $this->assertStringNotContainsString('.home-page .home-product-section article.np-product-card', $centralizedBlock);
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
