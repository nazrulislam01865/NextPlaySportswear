<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Services\Promotions\SaleBannerService;
use App\Services\Promotions\SaleCampaignService;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPromotionBannerPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_banner_wins_then_campaign_direct_media_then_global_fallback_without_category_leakage(): void
    {
        $related = $this->category('Related', 'category-related');
        $unrelated = $this->category('Unrelated', 'category-unrelated');
        $campaign = $this->campaign($related);
        $linked = $this->banner($campaign, 'Linked', priority: 1);
        $global = $this->banner(null, 'Global', priority: 5);

        $campaigns = app(SaleCampaignService::class);
        $banners = app(SaleBannerService::class);

        $relatedCandidates = $campaigns->bannerCandidates(PromotionBannerPlacement::CATEGORY_TOP, $related->id);
        $relatedResult = $banners->resolvePageSlot($relatedCandidates, PromotionBannerPlacement::CATEGORY_TOP);
        $this->assertSame($linked->id, $relatedResult['id']);

        $linked->delete();
        $directResult = app(SaleBannerService::class)->resolvePageSlot(
            $relatedCandidates,
            PromotionBannerPlacement::CATEGORY_TOP
        );
        $this->assertSame('campaign', $directResult['source']);
        $this->assertSame($campaign->id, $directResult['campaign_id']);

        $unrelatedCandidates = $campaigns->bannerCandidates(PromotionBannerPlacement::CATEGORY_TOP, $unrelated->id);
        $this->assertSame([], $unrelatedCandidates);
        $globalResult = app(SaleBannerService::class)->resolvePageSlot(
            $unrelatedCandidates,
            PromotionBannerPlacement::CATEGORY_TOP
        );
        $this->assertSame($global->id, $globalResult['id']);
    }

    public function test_category_controller_has_only_the_top_promotion_slot(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Storefront/CategoryController.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('PromotionBannerPlacement::CATEGORY_TOP', $source);
        $this->assertStringNotContainsString('CATEGORY_MIDDLE', $source);
        $this->assertStringNotContainsString('category_middle', $source);
    }

    private function category(string $name, string $slug): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'display_type' => 'collection',
            'status' => 'active',
            'description' => $name,
            'image_url' => '/images/'.$slug.'.jpg',
            'image_alt' => $name,
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 1,
        ]);
    }

    private function campaign(Category $category): SaleCampaign
    {
        $campaign = SaleCampaign::query()->create([
            'name' => 'Category campaign',
            'internal_code' => 'CT-12AB',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'product_categories',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 10,
            'banner_image_path' => 'promotions/campaigns/category.jpg',
            'banner_placements' => [PromotionBannerPlacement::CATEGORY_TOP],
            'banner_heading' => 'Category offer',
            'banner_alt_text' => 'Category offer',
            'banner_cta_label' => 'Shop now',
            'banner_destination_link' => '/products',
        ]);
        $campaign->categories()->sync([$category->id]);

        return $campaign;
    }

    private function banner(?SaleCampaign $campaign, string $heading, int $priority): SaleBanner
    {
        return SaleBanner::query()->create([
            'sale_campaign_id' => $campaign?->id,
            'name' => $heading,
            'desktop_image_path' => 'promotions/'.strtolower($heading).'.jpg',
            'alt_text' => $heading,
            'heading' => $heading,
            'cta_label' => 'Shop',
            'destination_link' => '/products',
            'placements' => [PromotionBannerPlacement::CATEGORY_TOP],
            'priority' => $priority,
            'inherit_campaign_schedule' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
