<?php

namespace Tests\Feature\Admin;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_campaign_and_inherited_banner_show_the_same_runtime_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29 12:00:00', 'UTC')); // Tuesday
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $campaign = SaleCampaign::query()->create([
            'name' => 'Weekday campaign',
            'internal_code' => 'WC-12AB',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at' => '2026-10-31 23:59:59',
            'timezone' => 'UTC',
            'repeat_weekdays' => true,
            'weekdays' => ['Mon'],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
        ]);
        SaleBanner::query()->create([
            'sale_campaign_id' => $campaign->id,
            'name' => 'Linked',
            'desktop_image_path' => 'promotions/banner.jpg',
            'alt_text' => 'Linked',
            'heading' => 'Linked',
            'cta_label' => 'Shop',
            'destination_link' => '/sale',
            'placements' => [SaleBanner::PLACEMENT_SALE_TOP],
            'priority' => 1,
            'inherit_campaign_schedule' => true,
            'timezone' => 'UTC',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin, 'admin')->get(route('admin.promotions.sales.index'))->assertSee('Paused today');
        $this->actingAs($admin, 'admin')->get(route('admin.promotions.banners.index'))->assertSee('Paused today');
    }
}
