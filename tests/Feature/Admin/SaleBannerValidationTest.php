<?php

namespace Tests\Feature\Admin;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaleBannerValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_retired_product_placements_and_unsafe_destinations_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.banners.store'), $this->payload([
            'placements' => ['product_top'],
        ]))->assertSessionHasErrors('placements.0');

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.banners.store'), $this->payload([
            'destination_link' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors('destination_link');
    }

    public function test_inherited_schedule_requires_campaign_and_independent_schedule_requires_valid_range(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.banners.store'), $this->payload([
            'inherit_campaign_schedule' => '1',
            'sale_campaign_id' => '',
        ]))->assertSessionHasErrors('sale_campaign_id');

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.banners.store'), $this->payload([
            'inherit_campaign_schedule' => '0',
            'start_date' => '2026-10-01',
            'start_time' => '12:00',
            'end_date' => '2026-10-01',
            'end_time' => '11:00',
        ]))->assertSessionHasErrors('end_date');
    }

    public function test_current_shared_storefront_placements_are_accepted(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.banners.store'), $this->payload([
            'sale_campaign_id' => $campaign->id,
            'placements' => [
                SaleBanner::PLACEMENT_SALE_TOP,
                SaleBanner::PLACEMENT_SALE_MIDDLE,
                SaleBanner::PLACEMENT_ALL_PRODUCTS_TOP,
                SaleBanner::PLACEMENT_ALL_PRODUCTS_MIDDLE,
                SaleBanner::PLACEMENT_CATEGORY_TOP,
            ],
        ]))->assertSessionHasNoErrors();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Banner',
            'sale_campaign_id' => '',
            'desktop_image' => UploadedFile::fake()->image('banner.jpg', 1440, 300),
            'alt_text' => 'Banner artwork',
            'heading' => 'Banner heading',
            'cta_label' => 'Shop Sale',
            'destination_link' => '/sale',
            'placements' => [SaleBanner::PLACEMENT_SALE_TOP],
            'priority' => 1,
            'inherit_campaign_schedule' => '0',
            'timezone' => 'UTC',
            'start_date' => '2026-09-01',
            'start_time' => '00:00',
            'end_date' => '2026-12-31',
            'end_time' => '23:59',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function campaign(): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => 'Campaign',
            'internal_code' => 'CP-12AB',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at' => '2026-12-31 23:59:59',
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
        ]);
    }
}
