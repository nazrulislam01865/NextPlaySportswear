<?php

namespace Tests\Feature\Storefront;

use App\Models\SaleCampaign;
use App\Services\Promotions\PromotionBannerPositionService;
use App\Services\Promotions\SaleBannerService;
use App\Services\Promotions\SaleCampaignService;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllProductsPromotionBannerPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_selected_all_products_placement_resolves_no_banner(): void
    {
        $this->campaign([]);
        $campaigns = app(SaleCampaignService::class);
        $banners = app(SaleBannerService::class);

        $top = $banners->resolvePageSlot(
            $campaigns->bannerCandidates(PromotionBannerPlacement::ALL_PRODUCTS_TOP),
            PromotionBannerPlacement::ALL_PRODUCTS_TOP
        );
        $middle = $banners->resolvePageSlot(
            $campaigns->bannerCandidates(PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE),
            PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE
        );

        $this->assertNull($top);
        $this->assertNull($middle);
    }

    public function test_top_and_middle_are_independent_single_page_slots_for_all_products(): void
    {
        $campaign = $this->campaign([
            PromotionBannerPlacement::ALL_PRODUCTS_TOP,
            PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE,
        ], showSalePage: false);

        $campaigns = app(SaleCampaignService::class);
        $banners = app(SaleBannerService::class);

        $top = $banners->resolvePageSlot(
            $campaigns->bannerCandidates(PromotionBannerPlacement::ALL_PRODUCTS_TOP),
            PromotionBannerPlacement::ALL_PRODUCTS_TOP
        );
        $middle = $banners->resolvePageSlot(
            $campaigns->bannerCandidates(PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE),
            PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE
        );

        $this->assertSame($campaign->id, $top['campaign_id']);
        $this->assertSame($campaign->id, $middle['campaign_id']);
        $this->assertSame('campaign', $top['source']);
        $this->assertSame('campaign', $middle['source']);
    }

    public function test_five_visual_rows_insert_after_row_two_and_one_row_has_no_middle(): void
    {
        $positions = app(PromotionBannerPositionService::class);

        $this->assertSame(8, $positions->insertionIndex(17, 4));
        $this->assertNull($positions->insertionIndex(4, 5));
    }

    public function test_ajax_partial_never_contains_the_top_slot(): void
    {
        $full = file_get_contents(resource_path('views/storefront/products/index.blade.php'));
        $partial = file_get_contents(resource_path('views/storefront/products/_results.blade.php'));

        $this->assertIsString($full);
        $this->assertIsString($partial);
        $this->assertStringContainsString('$allProductsTopBanner', $full);
        $this->assertStringContainsString('$allProductsMiddleBanner', $partial);
        $this->assertStringNotContainsString('$allProductsTopBanner', $partial);
    }

    private function campaign(array $placements, bool $showSalePage = true): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => 'All Products campaign',
            'internal_code' => 'AP-'.strtoupper(substr(md5((string) microtime(true)), 0, 6)),
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => $showSalePage,
            'priority' => 10,
            'banner_image_path' => 'promotions/campaigns/all-products.jpg',
            'banner_placements' => $placements,
            'banner_heading' => 'All Products offer',
            'banner_alt_text' => 'All Products offer',
            'banner_cta_label' => 'Shop now',
            'banner_destination_link' => '/products',
        ]);
    }
}
