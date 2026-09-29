<?php

namespace Tests\Feature\Promotions;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedBannerPlacementMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_values_are_exactly_the_five_spec_values(): void
    {
        $this->assertSame([
            'sale_top',
            'sale_middle',
            'all_products_top',
            'all_products_middle',
            'category_top',
        ], PromotionBannerPlacement::ALL);
    }

    public function test_migration_maps_legacy_banner_values_and_backfills_campaign_banner(): void
    {
        $banner = SaleBanner::query()->create([
            'name' => 'Legacy',
            'desktop_image_path' => 'promotions/legacy.jpg',
            'alt_text' => 'Legacy',
            'heading' => 'Legacy',
            'cta_label' => 'Shop',
            'destination_link' => '/sale',
            'placements' => ['sale_after_row_2', 'product_top', 'product_after_row_2', 'sale_top', 'category_top'],
            'priority' => 1,
            'inherit_campaign_schedule' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $campaign = SaleCampaign::query()->create([
            'name' => 'Legacy campaign',
            'status' => 'draft',
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
            'banner_image_path' => 'promotions/campaign.jpg',
            'banner_placements' => null,
        ]);

        DB::table('sale_banners')->where('id', $banner->id)->update([
            'placements' => json_encode(['sale_after_row_2', 'product_top', 'product_after_row_2', 'sale_top', 'category_top']),
        ]);
        DB::table('sale_campaigns')->where('id', $campaign->id)->update(['banner_placements' => null]);

        $migration = require database_path('migrations/2026_09_29_200000_unify_campaign_and_banner_placements.php');
        $migration->up();

        $this->assertSame([
            PromotionBannerPlacement::SALE_MIDDLE,
            PromotionBannerPlacement::ALL_PRODUCTS_TOP,
            PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE,
            PromotionBannerPlacement::SALE_TOP,
            PromotionBannerPlacement::CATEGORY_TOP,
        ], $banner->fresh()->placements);
        $this->assertSame([PromotionBannerPlacement::SALE_TOP], $campaign->fresh()->banner_placements);
    }
}
