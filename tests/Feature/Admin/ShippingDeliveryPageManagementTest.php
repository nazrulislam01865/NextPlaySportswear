<?php

namespace Tests\Feature\Admin;

use App\Models\ShippingDeliveryPageSetting;
use App\Models\User;
use App\Services\Storefront\ShippingDeliveryPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShippingDeliveryPageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_admin_can_save_managed_content_and_all_seven_icon_slots(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = $this->payload();
        $payload['hero']['title'] = 'MANAGED ORDER INFORMATION';
        $payload['info_card_icon_0'] = UploadedFile::fake()->image('card0.png');
        $payload['info_card_icon_1'] = UploadedFile::fake()->image('card1.png');
        $payload['notice_icon'] = UploadedFile::fake()->image('notice.png');
        for ($i = 0; $i < 4; $i++) { $payload['checklist_icon_'.$i] = UploadedFile::fake()->image("checklist-$i.png"); }

        $this->actingAs($admin, 'admin')->put(route('admin.shipping-delivery-page.update'), $payload)
            ->assertRedirect(route('admin.shipping-delivery-page.edit'));

        $setting = ShippingDeliveryPageSetting::query()->firstOrFail();
        $this->assertSame('MANAGED ORDER INFORMATION', $setting->hero['title']);
        $this->assertStringStartsWith('shipping-delivery-page/info-cards/icons/', $setting->info_cards['cards'][0]['icon_path']);
        $this->assertStringStartsWith('shipping-delivery-page/notice/icons/', $setting->notice['icon_path']);
        $this->assertStringStartsWith('shipping-delivery-page/address-checklist/icons/', $setting->address_checklist['items'][3]['icon_path']);
    }


    public function test_failed_database_save_rolls_back_staged_upload_and_preserves_previous_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('shipping-delivery-page/notice/icons/current.png', 'current');
        $defaults = app(ShippingDeliveryPageService::class)->defaults();
        $defaults['notice']['icon_path'] = 'shipping-delivery-page/notice/icons/current.png';
        ShippingDeliveryPageSetting::query()->create($defaults);

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = $this->payload();
        $payload['notice_icon'] = UploadedFile::fake()->image('new.png');

        // Match the existing About-page integration pattern: authentication remains
        // available for this request while persistence fails the updated_by FK.
        $admin->delete();
        $this->actingAs($admin, 'admin')->put(route('admin.shipping-delivery-page.update'), $payload);

        Storage::disk('public')->assertExists('shipping-delivery-page/notice/icons/current.png');
        $this->assertSame(
            'shipping-delivery-page/notice/icons/current.png',
            ShippingDeliveryPageSetting::query()->firstOrFail()->notice['icon_path']
        );
        $this->assertCount(1, Storage::disk('public')->allFiles('shipping-delivery-page/notice/icons'));
    }

    private function payload(): array
    {
        $payload = app(ShippingDeliveryPageService::class)->defaults();
        foreach ($payload['info_cards']['cards'] as &$card) { unset($card['icon_path'], $card['fallback_icon']); }
        unset($card);
        unset($payload['notice']['icon_path'], $payload['notice']['fallback_icon']);
        foreach ($payload['address_checklist']['items'] as &$item) { unset($item['icon_path'], $item['fallback_icon']); }
        unset($item);
        return $payload;
    }
}
