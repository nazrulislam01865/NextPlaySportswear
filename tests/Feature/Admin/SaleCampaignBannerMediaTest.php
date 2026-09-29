<?php

namespace Tests\Feature\Admin;

use App\Models\SaleCampaign;
use App\Models\User;
use App\Services\Promotions\SaleCampaignService;
use App\Support\PromotionBannerPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaleCampaignBannerMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_desktop_and_mobile_upload_replace_and_remove_independently(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.sales.store'), $this->payload([
            'campaign_name' => 'Responsive campaign',
            'banner_image' => UploadedFile::fake()->image('desktop.jpg', 1200, 250),
            'banner_mobile_image' => UploadedFile::fake()->image('mobile.jpg', 750, 600),
        ]))->assertSessionHasNoErrors();

        $campaign = SaleCampaign::query()->where('name', 'Responsive campaign')->firstOrFail();
        $oldDesktop = (string) $campaign->banner_image_path;
        $oldMobile = (string) $campaign->banner_mobile_image_path;
        Storage::disk('public')->assertExists($oldDesktop);
        Storage::disk('public')->assertExists($oldMobile);

        $this->actingAs($admin, 'admin')->put(route('admin.promotions.sales.update', $campaign), $this->payload([
            'campaign_name' => 'Responsive campaign',
            'banner_image' => UploadedFile::fake()->image('desktop-new.jpg', 1200, 250),
            'remove_banner_mobile_image' => '1',
        ]))->assertSessionHasNoErrors();

        $campaign->refresh();
        $this->assertNotSame($oldDesktop, $campaign->banner_image_path);
        $this->assertNull($campaign->banner_mobile_image_path);
        Storage::disk('public')->assertMissing($oldDesktop);
        Storage::disk('public')->assertMissing($oldMobile);
        Storage::disk('public')->assertExists((string) $campaign->banner_image_path);
    }

    public function test_campaign_payload_exposes_mobile_url_and_null_mobile_falls_back_to_desktop_component(): void
    {
        $campaign = SaleCampaign::query()->create([
            'name' => 'Payload campaign',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
            'banner_image_path' => 'promotions/sale-campaigns/1/desktop.jpg',
            'banner_mobile_image_path' => null,
            'banner_placements' => [PromotionBannerPlacement::SALE_TOP],
        ]);

        $candidate = collect(app(SaleCampaignService::class)->bannerCandidates(PromotionBannerPlacement::SALE_TOP))
            ->firstWhere('id', $campaign->id);

        $this->assertIsArray($candidate);
        $this->assertNotEmpty($candidate['campaign_banner']['desktop_image_url']);
        $this->assertNull($candidate['campaign_banner']['mobile_image_url']);
        $this->assertSame([PromotionBannerPlacement::SALE_TOP], $candidate['placements']);
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
