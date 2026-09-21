<?php

namespace Tests\Feature\Admin;

use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\Storefront\StorefrontSettingsService;
use App\Support\PublicMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NavigationSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_defaults_are_independent_from_header_defaults(): void
    {
        $service = app(StorefrontSettingsService::class);

        $navigation = $service->navigation();
        $header = $service->header();

        $this->assertSame(
            ['SHOP', 'SPORTS', 'MEN', 'WOMEN', 'KIDS', 'CUSTOM TEAMWEAR', 'EXPLORE'],
            array_column($navigation['items'], 'label')
        );
        $this->assertTrue($navigation['items'][0]['mega_menu']['enabled']);
        $this->assertSame('Top Choices', $navigation['items'][0]['mega_menu']['top_choices']['eyebrow']);
        $this->assertSame('NEW ARRIVALS', $navigation['items'][0]['mega_menu']['columns'][0]['title']);
        $this->assertSame('/images/storefront-vue/navigation/shop-mega-promo.jpg', $navigation['items'][0]['mega_menu']['promo']['image']);
        $this->assertArrayNotHasKey('navigation', $header);
    }

    public function test_updating_header_does_not_change_navigation_row(): void
    {
        $service = app(StorefrontSettingsService::class);
        $service->updateNavigation([
            'items' => [[
                'enabled' => true,
                'label' => 'TEAMS',
                'url' => '/bulk-quote',
                'target' => '_self',
                'mega_menu' => ['enabled' => false],
            ]],
        ]);

        $service->updateHeader([
            'branding' => ['logo' => '/logo.png', 'logo_alt' => 'NextPlay'],
            'announcements' => [],
            'utility_links' => [],
            'actions' => [],
        ]);

        $this->assertSame('TEAMS', $service->navigation()['items'][0]['label']);
        $this->assertArrayNotHasKey('navigation', $service->header());
    }

    public function test_extraction_migration_copies_customized_header_navigation_when_navigation_row_is_absent(): void
    {
        StorefrontSetting::query()->create([
            'key' => 'header',
            'settings' => [
                'navigation' => [
                    'items' => [[
                        'enabled' => true,
                        'label' => 'CUSTOM SHOP',
                        'url' => '/custom-shop',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ]],
                ],
            ],
        ]);

        $migration = require database_path('migrations/2026_09_21_160000_extract_navigation_from_header_settings.php');
        $migration->up();

        $stored = StorefrontSetting::query()->where('key', 'navigation')->firstOrFail();
        $this->assertSame('CUSTOM SHOP', $stored->settings['items'][0]['label']);
    }

    public function test_extraction_migration_never_overwrites_existing_navigation_row(): void
    {
        StorefrontSetting::query()->create([
            'key' => 'header',
            'settings' => [
                'navigation' => [
                    'items' => [[
                        'enabled' => true,
                        'label' => 'HEADER COPY',
                        'url' => '/header-copy',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ]],
                ],
            ],
        ]);
        StorefrontSetting::query()->create([
            'key' => 'navigation',
            'settings' => [
                'items' => [[
                    'enabled' => true,
                    'label' => 'ALREADY SAVED',
                    'url' => '/already-saved',
                    'target' => '_self',
                    'mega_menu' => ['enabled' => false],
                ]],
            ],
        ]);

        $migration = require database_path('migrations/2026_09_21_160000_extract_navigation_from_header_settings.php');
        $migration->up();

        $stored = StorefrontSetting::query()->where('key', 'navigation')->firstOrFail();
        $this->assertSame('ALREADY SAVED', $stored->settings['items'][0]['label']);
    }

    public function test_super_admin_can_open_navigation_settings_independently(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.navigation-settings.edit'))
            ->assertOk()
            ->assertSee('Navigation Menu')
            ->assertSee('SHOP')
            ->assertSee('Mega Menu');
    }

    public function test_header_settings_page_no_longer_contains_navigation_editor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.header-settings.edit'))
            ->assertOk()
            ->assertDontSee('Navigation Menu &amp; Mega Menu', false)
            ->assertDontSee('data-navigation-editor', false);
    }

    public function test_navigation_update_does_not_modify_header_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(StorefrontSettingsService::class);

        $service->updateHeader([
            'branding' => ['logo' => '/logo.png', 'logo_alt' => 'Keep Me'],
            'announcements' => [['enabled' => true, 'text' => 'Keep announcement', 'url' => null, 'dismissible' => true]],
            'utility_links' => [],
            'actions' => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.navigation-settings.update'), [
                'navigation' => [
                    'items' => [[
                        'enabled' => '1',
                        'label' => 'SHOP UPDATED',
                        'url' => '/products',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => '0'],
                    ]],
                ],
            ])
            ->assertRedirect(route('admin.navigation-settings.edit'));

        $this->assertSame('Keep Me', $service->header()['branding']['logo_alt']);
        $this->assertSame('Keep announcement', $service->header()['announcements'][0]['text']);
        $this->assertSame('SHOP UPDATED', $service->navigation()['items'][0]['label']);
    }

    public function test_navigation_promo_image_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $payload = [
            'navigation' => [
                'items' => [
                    'shop' => [
                        'enabled' => '1',
                        'label' => 'SHOP',
                        'url' => '/products',
                        'target' => '_self',
                        'mega_menu' => [
                            'enabled' => '1',
                            'promo' => [
                                'enabled' => '1',
                                'alt' => 'Promo',
                                'label' => 'Shop All',
                                'url' => '/products',
                                'image_file' => UploadedFile::fake()->image('promo.png', 800, 800),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.navigation-settings.update'), $payload)
            ->assertRedirect(route('admin.navigation-settings.edit'));

        $first = app(StorefrontSettingsService::class)->navigation()['items'][0]['mega_menu']['promo']['image'];
        $this->assertStringStartsWith('/media/storefront/settings/navigation/mega-menu/', $first);
        Storage::disk('public')->assertExists(PublicMedia::storedPathFromUrl($first));

        $payload['navigation']['items']['shop']['mega_menu']['promo'] = [
            'enabled' => '1',
            'alt' => 'Promo',
            'label' => 'Shop All',
            'url' => '/products',
            'image' => $first,
            'remove_image' => '1',
        ];

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.navigation-settings.update'), $payload)
            ->assertRedirect(route('admin.navigation-settings.edit'));

        $this->assertNull(app(StorefrontSettingsService::class)->navigation()['items'][0]['mega_menu']['promo']['image']);
        Storage::disk('public')->assertMissing(PublicMedia::storedPathFromUrl($first));
    }

    public function test_legacy_menu_admin_routes_are_not_registered(): void
    {
        $this->assertFalse(app('router')->has('admin.menus.index'));
        $this->assertFalse(app('router')->has('admin.menus.create'));
        $this->assertFalse(app('router')->has('admin.menus.store'));
        $this->assertFalse(app('router')->has('admin.menus.edit'));
        $this->assertFalse(app('router')->has('admin.menus.update'));
        $this->assertFalse(app('router')->has('admin.menus.destroy'));
    }

    public function test_admin_sidebar_shows_new_navigation_menu_and_not_legacy_catalog_menu(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Navigation Menu')
            ->assertDontSee('Navigation Menus');
    }
}
