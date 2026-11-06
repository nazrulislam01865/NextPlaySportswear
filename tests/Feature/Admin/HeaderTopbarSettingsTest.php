<?php

namespace Tests\Feature\Admin;

use App\Models\StorefrontPageSetting;
use App\Models\User;
use App\Services\Storefront\HeaderTopbarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HeaderTopbarSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(HeaderTopbarService::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace(app(HeaderTopbarService::class)->defaults(), $overrides);
    }

    public function test_super_admin_can_open_editor_and_see_it_in_storefront_settings_sidebar(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.header-topbar-settings.edit'))
            ->assertOk()
            ->assertSee('Storefront Settings')
            ->assertSee('Header &amp; Top Bar Settings', false)
            ->assertSee('Save Top Bar Settings');
    }

    public function test_saved_settings_update_the_real_storefront_topbar_and_clear_cache(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(HeaderTopbarService::class);
        $service->settings(); // Warm cache before updating.

        $this->actingAs($admin, 'admin')
            ->put(route('admin.header-topbar-settings.update'), $this->payload([
                'announcement_text' => 'TEAM KITS • MADE TO WIN',
                'track_order_label' => 'Order Status',
                'whatsapp_label' => 'Chat with Us',
                'whatsapp_number' => '+8801700000000',
                'whatsapp_url' => '',
                'instagram_enabled' => false,
                'facebook_url' => 'https://www.facebook.com/nextplay-test',
                'background_color' => '#123456',
                'text_color' => '#FFFFFF',
                'links_gap_px' => 14,
            ]))
            ->assertRedirect(route('admin.header-topbar-settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('TEAM KITS • MADE TO WIN', $service->settings()['announcement_text']);
        $this->assertDatabaseHas('storefront_page_settings', ['page_key' => HeaderTopbarService::PAGE_KEY]);

        $html = Blade::render('<x-storefront.topbar />');
        $this->assertStringContainsString('TEAM KITS', $html);
        $this->assertStringContainsString('Order Status', $html);
        $this->assertStringContainsString('Chat with Us', $html);
        $this->assertStringContainsString('https://wa.me/8801700000000', $html);
        $this->assertStringContainsString('https://www.facebook.com/nextplay-test', $html);
        $this->assertStringContainsString('background: #123456;', $html);
        $this->assertStringNotContainsString('data-header-analytics-label="instagram"', $html);
    }

    public function test_visibility_switches_hide_selected_elements_without_hiding_other_links(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.header-topbar-settings.update'), $this->payload([
                'announcement_enabled' => false,
                'whatsapp_enabled' => false,
                'facebook_enabled' => false,
                'instagram_enabled' => false,
                'youtube_enabled' => false,
                'tiktok_enabled' => false,
            ]))->assertSessionHasNoErrors();

        $html = Blade::render('<x-storefront.topbar />');
        $this->assertStringContainsString('np-utility-bar', $html);
        $this->assertStringContainsString('Track Order', $html);
        $this->assertStringNotContainsString('np-utility-marquee-track', $html);
        $this->assertStringNotContainsString('np-whatsapp-link', $html);
        $this->assertStringNotContainsString('np-social-links', $html);
    }

    public function test_master_switch_hides_the_entire_header_bar(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.header-topbar-settings.update'), $this->payload(['bar_enabled' => false]))
            ->assertSessionHasNoErrors();

        $this->assertStringNotContainsString('np-utility-bar', Blade::render('<x-storefront.topbar />'));
    }

    public function test_invalid_or_unsafe_links_and_colors_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.header-topbar-settings.update'), $this->payload([
                'instagram_url' => 'javascript:alert(1)',
                'background_color' => 'red;position:fixed',
            ]))
            ->assertSessionHasErrors(['instagram_url', 'background_color']);

        $this->assertDatabaseMissing('storefront_page_settings', ['page_key' => HeaderTopbarService::PAGE_KEY]);
    }

    public function test_users_without_manage_permission_cannot_write_topbar_settings(): void
    {
        $viewer = User::factory()->create(['role' => 'support_agent', 'is_active' => true]);

        $this->actingAs($viewer, 'admin')
            ->put(route('admin.header-topbar-settings.update'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('storefront_page_settings', ['page_key' => HeaderTopbarService::PAGE_KEY]);
    }
}
