<?php

namespace Tests\Feature\Admin;

use App\Models\SaleCampaign;
use App\Models\User;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaleCampaignPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_valid_placements_persist_in_submitted_order_and_forged_value_is_rejected(): void
    {
        $admin = $this->admin();
        $placements = [
            PromotionBannerPlacement::CATEGORY_TOP,
            PromotionBannerPlacement::SALE_MIDDLE,
            PromotionBannerPlacement::ALL_PRODUCTS_TOP,
        ];

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.sales.store'), $this->payload([
            'banner_placements' => $placements,
            'banner_placements_present' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertSame($placements, SaleCampaign::query()->firstOrFail()->banner_placements);

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.sales.store'), $this->payload([
            'campaign_name' => 'Forged placement',
            'banner_placements' => ['homepage_middle'],
            'banner_placements_present' => '1',
        ]))->assertSessionHasErrors('banner_placements.0');
    }

    public function test_new_campaign_desktop_upload_defaults_to_sale_top_only_when_placements_are_not_submitted(): void
    {
        $admin = $this->admin();

        $payload = $this->payload([
            'campaign_name' => 'Default placement campaign',
            'banner_image' => UploadedFile::fake()->image('desktop.jpg', 1200, 250),
            'banner_placements_present' => '0',
        ]);
        unset($payload['banner_placements']);

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.sales.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [PromotionBannerPlacement::SALE_TOP],
            SaleCampaign::query()->where('name', 'Default placement campaign')->firstOrFail()->banner_placements
        );
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'campaign_name' => 'Campaign',
            'status' => 'draft',
            'submit_action' => 'draft',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'maximum_discount' => '',
            'start_date' => '2026-09-29',
            'start_time' => '09:00',
            'end_date' => '2026-10-29',
            'end_time' => '18:00',
            'timezone' => 'UTC',
            'repeat_weekdays' => '0',
            'weekdays' => [],
            'applies_to' => 'all',
            'target_ids' => [],
            'excluded_product_ids' => [],
            'show_sale_badge' => '1',
            'show_sale_page' => '1',
            'priority' => 1,
            'remove_banner_image' => '0',
            'remove_banner_mobile_image' => '0',
            'banner_placements_present' => '1',
            'banner_placements' => [PromotionBannerPlacement::SALE_TOP],
            'banner_heading' => '',
            'banner_alt_text' => '',
            'banner_cta_label' => 'Shop Sale',
            'banner_destination_link' => '/sale',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }
}
