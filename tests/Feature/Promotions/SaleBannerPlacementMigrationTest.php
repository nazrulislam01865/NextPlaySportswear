<?php

namespace Tests\Feature\Promotions;

use App\Models\SaleBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SaleBannerPlacementMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_all_products_and_sale_row_placements_map_to_shared_slots(): void
    {
        $banner = $this->banner(['sale_top']);

        DB::table('sale_banners')->where('id', $banner->id)->update([
            'placements' => json_encode(['product_top', 'sale_after_row_2', 'product_after_row_2', 'sale_top', 'category_top']),
        ]);

        $migration = require database_path('migrations/2026_09_29_200000_unify_campaign_and_banner_placements.php');
        $migration->up();

        $this->assertSame([
            'all_products_top',
            'sale_middle',
            'all_products_middle',
            'sale_top',
            'category_top',
        ], $banner->fresh()->placements);
    }

    private function banner(array $placements): SaleBanner
    {
        return SaleBanner::query()->create([
            'name' => 'Legacy banner '.uniqid(),
            'desktop_image_path' => 'promotions/legacy.jpg',
            'alt_text' => 'Legacy',
            'heading' => 'Legacy',
            'cta_label' => 'Shop',
            'destination_link' => '/sale',
            'placements' => $placements,
            'priority' => 1,
            'inherit_campaign_schedule' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
