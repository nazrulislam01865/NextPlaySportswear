<?php

namespace Tests\Unit\Promotions;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Services\Promotions\SaleBannerService;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleBannerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_banner_wins_over_campaign_direct_media_for_same_slot(): void
    {
        $campaign = $this->campaign('Campaign A', 'CA-12AB');
        $this->banner(null, 'Global', [PromotionBannerPlacement::SALE_TOP], priority: 3);
        $linked = $this->banner($campaign, 'Linked', [PromotionBannerPlacement::SALE_TOP], priority: 1);

        $resolved = app(SaleBannerService::class)->resolvePageSlot([
            $this->candidate($campaign, [PromotionBannerPlacement::SALE_TOP], 'Campaign direct'),
        ], PromotionBannerPlacement::SALE_TOP);

        $this->assertSame($linked->id, $resolved['id']);
        $this->assertSame('Linked', $resolved['heading']);
        $this->assertSame('banner', $resolved['source']);
    }

    public function test_campaign_direct_media_wins_over_global_banner_when_slot_is_selected(): void
    {
        $campaign = $this->campaign('Campaign A', 'CA-12AB');
        $this->banner(null, 'Global', [PromotionBannerPlacement::SALE_TOP], priority: 1);

        $resolved = app(SaleBannerService::class)->resolvePageSlot([
            $this->candidate($campaign, [PromotionBannerPlacement::SALE_TOP], 'Campaign direct'),
        ], PromotionBannerPlacement::SALE_TOP);

        $this->assertSame('Campaign direct', $resolved['heading']);
        $this->assertSame('campaign', $resolved['source']);
    }

    public function test_resolver_continues_to_next_campaign_before_global_fallback(): void
    {
        $campaignA = $this->campaign('Campaign A', 'CA-12AB');
        $campaignB = $this->campaign('Campaign B', 'CB-12AB');
        $this->banner(null, 'Global', [PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE], priority: 1);

        $resolved = app(SaleBannerService::class)->resolvePageSlot([
            $this->candidate($campaignA, [], 'A direct'),
            $this->candidate($campaignB, [PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE], 'B direct'),
        ], PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE);

        $this->assertSame('B direct', $resolved['heading']);
        $this->assertSame($campaignB->id, $resolved['campaign_id']);
    }

    public function test_display_priority_chooses_winning_linked_and_global_banner(): void
    {
        $campaign = $this->campaign('Campaign A', 'CA-12AB');
        $this->banner($campaign, 'Linked later', [PromotionBannerPlacement::CATEGORY_TOP], priority: 2, sortOrder: 1);
        $linkedWinner = $this->banner($campaign, 'Linked winner', [PromotionBannerPlacement::CATEGORY_TOP], priority: 1, sortOrder: 5);
        $this->banner(null, 'Global winner', [PromotionBannerPlacement::CATEGORY_TOP], priority: 1, sortOrder: 1);

        $resolved = app(SaleBannerService::class)->resolvePageSlot([
            $this->candidate($campaign, [], 'Unused'),
        ], PromotionBannerPlacement::CATEGORY_TOP);

        $this->assertSame($linkedWinner->id, $resolved['id']);
    }

    public function test_resolver_returns_one_global_payload_when_no_campaign_candidate_has_media(): void
    {
        $campaign = $this->campaign('Campaign A', 'CA-12AB');
        $global = $this->banner(null, 'Global', [PromotionBannerPlacement::SALE_MIDDLE], priority: 1);

        $resolved = app(SaleBannerService::class)->resolvePageSlot([
            $this->candidate($campaign, [], 'Unused'),
        ], PromotionBannerPlacement::SALE_MIDDLE);

        $this->assertSame($global->id, $resolved['id']);
        $this->assertSame('Global', $resolved['heading']);
    }

    private function campaign(string $name, string $code): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => $name,
            'internal_code' => $code,
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
            'show_sale_page' => true,
            'priority' => 1,
        ]);
    }

    private function banner(?SaleCampaign $campaign, string $heading, array $placements, int $priority = 1, int $sortOrder = 1): SaleBanner
    {
        return SaleBanner::query()->create([
            'sale_campaign_id' => $campaign?->id,
            'name' => $heading,
            'desktop_image_path' => 'promotions/'.strtolower(str_replace(' ', '-', $heading)).'.jpg',
            'alt_text' => $heading,
            'heading' => $heading,
            'cta_label' => 'Shop',
            'destination_link' => '/sale',
            'placements' => $placements,
            'priority' => $priority,
            'inherit_campaign_schedule' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function candidate(SaleCampaign $campaign, array $placements, string $heading): array
    {
        return [
            'id' => $campaign->id,
            'placements' => $placements,
            'campaign_banner' => [
                'id' => $campaign->id,
                'name' => $campaign->name.' campaign banner',
                'desktop_image_url' => '/media/campaign-'.$campaign->id.'.jpg',
                'mobile_image_url' => null,
                'placements' => $placements,
                'alt_text' => $heading,
                'heading' => $heading,
                'cta_label' => 'Shop Sale',
                'destination_link' => '/sale',
                'campaign_id' => $campaign->id,
                'source' => 'campaign',
            ],
        ];
    }
}
