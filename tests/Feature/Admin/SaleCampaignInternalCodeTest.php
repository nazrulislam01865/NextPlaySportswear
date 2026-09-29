<?php

namespace Tests\Feature\Admin;

use App\Models\SaleCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCampaignInternalCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ignores_forged_code_and_generates_from_name(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.promotions.sales.store'), $this->payload([
            'campaign_name' => 'Winter Deals',
            'internal_code' => 'FORGED-0000',
        ]))->assertSessionHasNoErrors();

        $campaign = SaleCampaign::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^WD-[A-Z0-9]{4}$/', (string) $campaign->internal_code);
        $this->assertNotSame('FORGED-0000', $campaign->internal_code);
    }

    public function test_edit_preserves_code_after_rename_and_legacy_blank_is_generated(): void
    {
        $admin = $this->admin();

        $campaign = $this->campaign('Winter Deals', 'WD-12AB');
        $this->actingAs($admin, 'admin')->put(route('admin.promotions.sales.update', $campaign), $this->payload([
            'campaign_name' => 'Renamed Campaign',
            'internal_code' => 'HACKED-9999',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('WD-12AB', $campaign->fresh()->internal_code);

        $legacy = $this->campaign('Summer Clearance Sale', null);
        $this->actingAs($admin, 'admin')->put(route('admin.promotions.sales.update', $legacy), $this->payload([
            'campaign_name' => 'Summer Clearance Sale',
        ]))->assertSessionHasNoErrors();
        $this->assertMatchesRegularExpression('/^SCS-[A-Z0-9]{4}$/', (string) $legacy->fresh()->internal_code);
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
            'banner_heading' => '',
            'banner_alt_text' => '',
            'banner_cta_label' => 'Shop Sale',
            'banner_destination_link' => '/sale',
        ], $overrides);
    }

    private function campaign(string $name, ?string $code): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => $name,
            'internal_code' => $code,
            'status' => 'draft',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => '2026-09-29 09:00:00',
            'ends_at' => '2026-10-29 18:00:00',
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }
}
