<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionBannerAdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_and_banner_editors_share_five_placements_and_ratio_guidance(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $labels = array_values(PromotionBannerPlacement::labels());

        $campaign = $this->actingAs($admin, 'admin')->get(route('admin.promotions.sales.create'));
        $campaign->assertOk()->assertSeeInOrder($labels, false);
        $campaign->assertSee('recommended ratio 24:5', false);
        $campaign->assertSee('recommended ratio 5:4', false);
        $campaign->assertDontSee('1440 × 300', false);
        $campaign->assertDontSee('750 × 600', false);

        $banner = $this->actingAs($admin, 'admin')->get(route('admin.promotions.banners.index', ['new' => 1]));
        $banner->assertOk()->assertSeeInOrder($labels, false);
        $banner->assertSee('recommended ratio 24:5', false);
        $banner->assertSee('recommended ratio 5:4', false);
        $banner->assertDontSee('1440 × 300', false);
        $banner->assertDontSee('750 × 600', false);
    }
}
