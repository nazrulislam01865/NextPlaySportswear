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

class PromotionBannerSlotResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_candidates_do_not_leak_to_unrelated_category_and_global_is_final_fallback(): void
    {
        $related = $this->category('Related', 'related');
        $unrelated = $this->category('Unrelated', 'unrelated');
        $campaign = $this->campaign();
        $campaign->categories()->sync([$related->id]);
        $campaign->forceFill([
            'banner_image_path' => 'promotions/campaign.jpg',
            'banner_placements' => [PromotionBannerPlacement::CATEGORY_TOP],
        ])->save();

        $global = $this->banner(null, 'Global', PromotionBannerPlacement::CATEGORY_TOP, 5);

        $campaigns = app(SaleCampaignService::class);
        $banners = app(SaleBannerService::class);

        $relatedCandidates = $campaigns->bannerCandidates(PromotionBannerPlacement::CATEGORY_TOP, $related->id);
        $unrelatedCandidates = $campaigns->bannerCandidates(PromotionBannerPlacement::CATEGORY_TOP, $unrelated->id);

        $this->assertSame([$campaign->id], collect($relatedCandidates)->pluck('id')->all());
        $this->assertSame([], collect($unrelatedCandidates)->pluck('id')->all());
        $this->assertSame('campaign', $banners->resolvePageSlot($relatedCandidates, PromotionBannerPlacement::CATEGORY_TOP)['source']);
        $this->assertSame($global->id, $banners->resolvePageSlot($unrelatedCandidates, PromotionBannerPlacement::CATEGORY_TOP)['id']);
    }

    public function test_linked_banner_wins_for_related_category(): void
    {
        $category = $this->category('Related', 'related');
        $campaign = $this->campaign();
        $campaign->categories()->sync([$category->id]);
        $campaign->forceFill([
            'banner_image_path' => 'promotions/campaign.jpg',
            'banner_placements' => [PromotionBannerPlacement::CATEGORY_TOP],
        ])->save();
        $linked = $this->banner($campaign, 'Linked', PromotionBannerPlacement::CATEGORY_TOP, 1);

        $candidates = app(SaleCampaignService::class)->bannerCandidates(PromotionBannerPlacement::CATEGORY_TOP, $category->id);
        $resolved = app(SaleBannerService::class)->resolvePageSlot($candidates, PromotionBannerPlacement::CATEGORY_TOP);

        $this->assertSame($linked->id, $resolved['id']);
        $this->assertSame('banner', $resolved['source']);
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

    private function campaign(): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => 'Category campaign',
            'internal_code' => 'CC-12AB',
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
        ]);
    }

    private function banner(?SaleCampaign $campaign, string $heading, string $placement, int $priority): SaleBanner
    {
        return SaleBanner::query()->create([
            'sale_campaign_id' => $campaign?->id,
            'name' => $heading,
            'desktop_image_path' => 'promotions/'.strtolower($heading).'.jpg',
            'alt_text' => $heading,
            'heading' => $heading,
            'cta_label' => 'Shop',
            'destination_link' => '/sale',
            'placements' => [$placement],
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
