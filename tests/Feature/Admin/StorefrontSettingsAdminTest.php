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

class StorefrontSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_dynamic_vue_header_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.header-settings.update'), [
                'announcements' => [
                    [
                        'enabled' => '1',
                        'text' => 'Free delivery over $250',
                        'url' => '/offers',
                        'dismissible' => '1',
                    ],
                    [
                        'enabled' => '1',
                        'text' => 'Team orders available',
                        'url' => '/bulk-quote',
                        'dismissible' => '0',
                    ],
                ],
                'utility_links' => [
                    [
                        'enabled' => '1',
                        'label' => 'Track Order',
                        'url' => '/account/orders',
                        'icon' => 'package',
                    ],
                    [
                        'enabled' => '1',
                        'label' => 'Support',
                        'url' => '/contact-us',
                        'icon' => 'headphones',
                    ],
                ],
                'search_enabled' => '1',
                'search_label' => 'Search',
                'search_url' => '/products',
                'account_enabled' => '1',
                'wishlist_enabled' => '1',
                'cart_enabled' => '1',
                'quote_enabled' => '1',
                'quote_label' => 'GET A QUOTE',
                'quote_url' => '/bulk-quote',
            ])
            ->assertRedirect(route('admin.header-settings.edit'));

        $stored = StorefrontSetting::query()->where('key', StorefrontSettingsService::HEADER_KEY)->firstOrFail();

        $this->assertCount(2, $stored->settings['announcements']);
        $this->assertSame('Free delivery over $250', $stored->settings['announcements'][0]['text']);
        $this->assertSame('Team orders available', $stored->settings['announcements'][1]['text']);
        $this->assertSame('Track Order', $stored->settings['utility_links'][0]['label']);
        $this->assertSame('package', $stored->settings['utility_links'][0]['icon']);
    }

    public function test_super_admin_can_update_dynamic_footer_columns_social_and_legal_links(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.footer-settings.update'), [
                'address' => 'NextPlay HQ',
                'email' => 'hello@nextplay.test',
                'phone' => '+1 555 0100',
                'columns' => [
                    [
                        'enabled' => '1',
                        'title' => 'Quick Links',
                        'items' => [
                            ['enabled' => '1', 'label' => 'Wishlist', 'url' => '/wishlist', 'icon' => 'heart'],
                            ['enabled' => '1', 'label' => 'Account', 'url' => '/account', 'icon' => 'user'],
                        ],
                    ],
                    [
                        'enabled' => '1',
                        'title' => 'Support',
                        'items' => [
                            ['enabled' => '1', 'label' => 'Contact', 'url' => '/contact-us', 'icon' => 'headphones'],
                        ],
                    ],
                ],
                'club_enabled' => '1',
                'club_title' => 'JOIN NEXTPLAY CLUB',
                'club_button_label' => 'SIGN UP',
                'club_button_url' => '/register',
                'social_enabled' => '1',
                'social_label' => 'Follow Us :',
                'social_links' => [
                    ['enabled' => '1', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/nextplaysportswear/', 'icon' => 'instagram'],
                    ['enabled' => '1', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/nextplay', 'icon' => 'linkedin'],
                ],
                'copyright' => '© {year} Nextplay Sportswear',
                'legal_links' => [
                    ['enabled' => '1', 'label' => 'Privacy Policy', 'url' => '/privacy-policy', 'icon' => 'shield-check'],
                ],
                'payments_enabled' => '1',
                'payments_label' => 'Secured by Stripe:',
            ])
            ->assertRedirect(route('admin.footer-settings.edit'));

        $footer = app(StorefrontSettingsService::class)->footer();

        $this->assertSame('NextPlay HQ', $footer['contact']['address']);
        $this->assertCount(2, $footer['columns']);
        $this->assertSame('heart', $footer['columns'][0]['items'][0]['icon']);
        $this->assertCount(2, $footer['social']['links']);
        $this->assertSame('linkedin', $footer['social']['links'][1]['icon']);
        $this->assertSame('Privacy Policy', $footer['legal']['links'][0]['label']);
        $this->assertSame('Secured by Stripe:', $footer['payments']['label']);
    }


    public function test_super_admin_can_upload_global_vue_storefront_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.header-settings.update'), [
                'branding' => [
                    'logo_alt' => 'NextPlay Sportswear',
                    'logo_file' => UploadedFile::fake()->image('nextplay-logo.png', 1200, 300),
                ],
                'search_enabled' => '1',
                'search_label' => 'Search',
                'search_url' => '/products',
                'account_enabled' => '1',
                'wishlist_enabled' => '1',
                'cart_enabled' => '1',
                'quote_enabled' => '1',
                'quote_label' => 'GET A QUOTE',
                'quote_url' => '/bulk-quote',
            ])
            ->assertRedirect(route('admin.header-settings.edit'));

        $header = app(StorefrontSettingsService::class)->header();
        $logo = (string) $header['branding']['logo'];

        $this->assertStringStartsWith('/media/storefront/settings/branding/', $logo);
        $this->assertSame('NextPlay Sportswear', $header['branding']['logo_alt']);
        Storage::disk('public')->assertExists(PublicMedia::storedPathFromUrl($logo));
    }

    public function test_super_admin_can_upload_header_utility_link_icon_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.header-settings.update'), [
                'utility_links' => [
                    'track' => [
                        'enabled' => '1',
                        'label' => 'Track Order',
                        'url' => '/account/orders',
                        'icon_file' => UploadedFile::fake()->image('track.png', 64, 64),
                    ],
                ],
                'search_enabled' => '1',
                'search_label' => 'Search',
                'search_url' => '/products',
                'account_enabled' => '1',
                'wishlist_enabled' => '1',
                'cart_enabled' => '1',
                'quote_enabled' => '1',
                'quote_label' => 'GET A QUOTE',
                'quote_url' => '/bulk-quote',
            ])
            ->assertRedirect(route('admin.header-settings.edit'));

        $header = app(StorefrontSettingsService::class)->header();
        $icon = (string) $header['utility_links'][0]['icon'];

        $this->assertStringStartsWith('/media/storefront/settings/icons/header/utility/', $icon);
        Storage::disk('public')->assertExists(PublicMedia::storedPathFromUrl($icon));
    }

    public function test_super_admin_can_upload_footer_menu_social_and_legal_icon_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.footer-settings.update'), [
                'columns' => [
                    'quick' => [
                        'enabled' => '1',
                        'title' => 'Quick Links',
                        'items' => [
                            'wishlist' => [
                                'enabled' => '1',
                                'label' => 'Wishlist',
                                'url' => '/wishlist',
                                'icon_file' => UploadedFile::fake()->image('wishlist.png', 64, 64),
                            ],
                        ],
                    ],
                ],
                'social_enabled' => '1',
                'social_label' => 'Follow Us :',
                'social_links' => [
                    'instagram' => [
                        'enabled' => '1',
                        'label' => 'Instagram',
                        'url' => 'https://www.instagram.com/nextplaysportswear/',
                        'icon_file' => UploadedFile::fake()->image('instagram.png', 64, 64),
                    ],
                ],
                'legal_links' => [
                    'privacy' => [
                        'enabled' => '1',
                        'label' => 'Privacy Policy',
                        'url' => '/privacy-policy',
                        'icon_file' => UploadedFile::fake()->image('privacy.png', 64, 64),
                    ],
                ],
                'payments_enabled' => '1',
            ])
            ->assertRedirect(route('admin.footer-settings.edit'));

        $footer = app(StorefrontSettingsService::class)->footer();
        $icons = [
            $footer['columns'][0]['items'][0]['icon'],
            $footer['social']['links'][0]['icon'],
            $footer['legal']['links'][0]['icon'],
        ];

        foreach ($icons as $icon) {
            $this->assertIsString($icon);
            $this->assertStringStartsWith('/media/storefront/settings/icons/footer/', $icon);
            Storage::disk('public')->assertExists(PublicMedia::storedPathFromUrl($icon));
        }
    }

    public function test_legacy_header_and_footer_json_is_normalized_without_losing_content(): void
    {
        StorefrontSetting::query()->create([
            'key' => StorefrontSettingsService::HEADER_KEY,
            'settings' => [
                'announcement' => [
                    'enabled' => true,
                    'text' => 'Legacy announcement',
                    'url' => '/offers',
                    'dismissible' => true,
                ],
                'utility_links' => [
                    'track_order' => ['enabled' => true, 'label' => 'Old Track', 'url' => '/account/orders'],
                ],
            ],
        ]);

        StorefrontSetting::query()->create([
            'key' => StorefrontSettingsService::FOOTER_KEY,
            'settings' => [
                'columns' => [
                    'quick_links' => [
                        'title' => 'Old Quick Links',
                        'items' => [['label' => 'Wishlist', 'url' => '/wishlist']],
                    ],
                ],
                'social' => [
                    'enabled' => true,
                    'label' => 'Follow Us :',
                    'instagram_url' => 'https://instagram.com/nextplay',
                ],
                'legal' => [
                    'copyright' => '© {year}',
                    'privacy_label' => 'Privacy',
                    'privacy_url' => '/privacy-policy',
                ],
            ],
        ]);

        $service = app(StorefrontSettingsService::class);
        $service->flushCache();

        $this->assertSame('Legacy announcement', $service->header()['announcements'][0]['text']);
        $this->assertSame('package', $service->header()['utility_links'][0]['icon']);
        $this->assertSame('Old Quick Links', $service->footer()['columns'][0]['title']);
        $this->assertSame('instagram', $service->footer()['social']['links'][0]['icon']);
        $this->assertSame('Privacy', $service->footer()['legal']['links'][0]['label']);
    }

    public function test_defaults_preserve_the_approved_vue_header_and_footer_content(): void
    {
        $settings = app(StorefrontSettingsService::class);

        $this->assertSame('Shop $200 and get free delivery', $settings->header()['announcements'][0]['text']);
        $this->assertSame('Track Your Order', $settings->header()['utility_links'][0]['label']);
        $this->assertSame('Quick Links', $settings->footer()['columns'][0]['title']);
        $this->assertSame('Wishlist', $settings->footer()['columns'][0]['items'][0]['label']);
        $this->assertSame('JOIN NEXTPLAY CLUB & GET 20% OFF', $settings->footer()['club']['title']);
        $this->assertSame('YouTube', $settings->footer()['social']['links'][0]['label']);
        $this->assertSame('Secured by Stripe:', $settings->footer()['payments']['label']);
    }

    public function test_header_update_preserves_independent_navigation_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(StorefrontSettingsService::class);

        $service->updateNavigation([
            'items' => [[
                'enabled' => true,
                'label' => 'CUSTOM NAV',
                'url' => '/custom-nav',
                'target' => '_self',
                'mega_menu' => ['enabled' => false],
            ]],
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.header-settings.update'), [
                'announcements' => [[
                    'enabled' => '1',
                    'text' => 'Updated header only',
                    'url' => '/offers',
                    'dismissible' => '1',
                ]],
                'search_enabled' => '1',
                'search_label' => 'Search',
                'search_url' => '/products',
                'account_enabled' => '1',
                'wishlist_enabled' => '1',
                'cart_enabled' => '1',
                'quote_enabled' => '1',
                'quote_label' => 'GET A QUOTE',
                'quote_url' => '/bulk-quote',
            ])
            ->assertRedirect(route('admin.header-settings.edit'));

        $this->assertSame('Updated header only', $service->header()['announcements'][0]['text']);
        $this->assertSame('CUSTOM NAV', $service->navigation()['items'][0]['label']);
        $this->assertArrayNotHasKey('navigation', $service->header());
    }

    public function test_legacy_managed_storage_urls_are_normalized_to_public_media_urls_on_read(): void
    {
        StorefrontSetting::query()->create([
            'key' => StorefrontSettingsService::HEADER_KEY,
            'settings' => [
                'branding' => [
                    'logo' => '/storage/storefront/settings/branding/legacy-logo.png',
                    'logo_alt' => 'NextPlay',
                ],
                'announcements' => [],
                'utility_links' => [],
                'actions' => [],
            ],
        ]);

        app(StorefrontSettingsService::class)->flushCache(StorefrontSettingsService::HEADER_KEY);

        $this->assertSame(
            '/media/storefront/settings/branding/legacy-logo.png',
            app(StorefrontSettingsService::class)->header()['branding']['logo']
        );
    }

}
