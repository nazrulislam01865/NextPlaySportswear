<?php

namespace Tests\Feature\Promotions;

use App\Models\SaleCampaign;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsistentProductOfferPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_campaign_updates_catalog_price_without_stacking_on_existing_product_discount(): void
    {
        $this->campaign(['discount_type' => 'percentage', 'discount_value' => 25]);

        $product = $this->catalogProduct(80, 100);
        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($product);

        $this->assertSame(75.0, $priced['display_unit_price']);
        $this->assertSame(100.0, $priced['original_price']);
        $this->assertSame(25, $priced['discount_percentage']);
        $this->assertSame('Sale', $priced['sale_badge_label']);
    }

    public function test_existing_product_discount_is_preserved_when_better_than_campaign(): void
    {
        $this->campaign(['discount_type' => 'percentage', 'discount_value' => 10]);

        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($this->catalogProduct(70, 100));

        $this->assertSame(70.0, $priced['display_unit_price']);
        $this->assertSame('Sale', $priced['sale_badge_label']);
    }

    public function test_fixed_discount_respects_maximum_discount(): void
    {
        $this->campaign(['discount_type' => 'fixed', 'discount_value' => 40, 'maximum_discount' => 15]);

        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($this->catalogProduct(100, 100));

        $this->assertSame(85.0, $priced['display_unit_price']);
    }

    public function test_product_specific_campaign_does_not_discount_unassigned_products(): void
    {
        $this->campaign(['applies_to' => 'products']);

        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($this->catalogProduct(100, 100));

        $this->assertSame(100.0, $priced['display_unit_price']);
        $this->assertNull($priced['sale_badge_label']);
    }

    public function test_future_campaign_never_discounts_the_product(): void
    {
        $this->campaign(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);

        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($this->catalogProduct(100, 100));

        $this->assertSame(100.0, $priced['display_unit_price']);
        $this->assertNull($priced['sale_badge_label']);
    }

    public function test_full_discount_displays_a_free_product_offer(): void
    {
        $this->campaign(['discount_type' => 'percentage', 'discount_value' => 100]);

        $priced = app(ProductCatalogService::class)->applySaleCampaignPricing($this->catalogProduct(100, 100));

        $this->assertSame(0.0, $priced['display_unit_price']);
        $this->assertSame('Sale', $priced['sale_badge_label']);
    }

    private function catalogProduct(float $price, float $original): array
    {
        return [
            'id' => 712,
            'category_ids' => [],
            'base_price' => $original,
            'display_unit_price' => $price,
            'original_price' => $original > $price ? $original : null,
            'price' => 'From $'.number_format($price, 2),
        ];
    }

    private function campaign(array $overrides): SaleCampaign
    {
        return SaleCampaign::query()->create(array_merge([
            'name' => 'Product Offer',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 10,
        ], $overrides));
    }
}
