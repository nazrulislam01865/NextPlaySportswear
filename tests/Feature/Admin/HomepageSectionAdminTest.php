<?php

namespace Tests\Feature\Admin;

use App\Models\HomepageSection;
use App\Models\User;
use App\Services\Storefront\HomepageSectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageSectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_section_settings_are_persisted_as_an_array(): void
    {
        $section = HomepageSection::query()->create([
            'key' => 'best_choices',
            'name' => 'Best Choices For You',
            'settings' => [
                'tabs' => [
                    'featured' => ['label' => 'FEATURED', 'enabled' => true],
                ],
            ],
            'is_active' => true,
            'sort_order' => 60,
        ]);

        $section->refresh();

        $this->assertSame('FEATURED', $section->settings['tabs']['featured']['label']);
        $this->assertTrue($section->settings['tabs']['featured']['enabled']);
    }

    public function test_legacy_homepage_rows_are_reused_without_deleting_the_legacy_rows(): void
    {
        HomepageSection::query()->create(['key' => 'latest_products', 'name' => 'Latest Products', 'title' => 'Legacy Latest Heading', 'is_active' => true, 'sort_order' => 95]);
        HomepageSection::query()->create(['key' => 'categories', 'name' => 'Categories', 'title' => 'Legacy Categories Heading', 'items' => [['category_id' => 10]], 'is_active' => true, 'sort_order' => 30]);
        HomepageSection::query()->create(['key' => 'shop_by_sport', 'name' => 'Shop By Sport', 'title' => 'Legacy Sports Heading', 'items' => [['category_id' => 20, 'title' => 'Basketball']], 'is_active' => true, 'sort_order' => 22]);
        HomepageSection::query()->create(['key' => 'process', 'name' => 'Process', 'title' => 'Legacy Process Heading', 'items' => [['title' => 'Legacy step']], 'is_active' => true, 'sort_order' => 80]);
        HomepageSection::query()->create(['key' => 'slider', 'name' => 'Slider', 'is_active' => false, 'sort_order' => 10]);

        $migration = require database_path('migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php');
        $migration->up();

        $this->assertSame('Legacy Latest Heading', HomepageSection::where('key', 'new_arrivals')->value('title'));
        $this->assertSame('Legacy Categories Heading', HomepageSection::where('key', 'shop_by_category')->value('title'));
        $this->assertSame('Legacy Process Heading', HomepageSection::where('key', 'design_process')->value('title'));
        $this->assertDatabaseHas('homepage_sections', ['key' => 'categories']);
        $this->assertDatabaseHas('homepage_sections', ['key' => 'process']);
        $this->assertFalse((bool) HomepageSection::where('key', 'hero')->value('is_active'));
        $this->assertSame('category-10', HomepageSection::where('key', 'shop_by_category')->firstOrFail()->items[0]['id']);
        $this->assertSame('sport-20', HomepageSection::where('key', 'shop_by_sport')->firstOrFail()->items[0]['id']);
        $this->assertSame('choose-product', HomepageSection::where('key', 'design_process')->firstOrFail()->items[0]['id']);
    }

    public function test_legacy_realign_does_not_overwrite_an_existing_custom_target_heading(): void
    {
        HomepageSection::query()->create(['key' => 'latest_products', 'name' => 'Latest Products', 'title' => 'Legacy Latest Heading', 'is_active' => true, 'sort_order' => 95]);
        HomepageSection::query()->create(['key' => 'new_arrivals', 'name' => 'New Arrivals', 'title' => 'Admin Custom New Arrivals', 'is_active' => true, 'sort_order' => 40]);

        $migration = require database_path('migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php');
        $migration->up();

        $this->assertSame('Admin Custom New Arrivals', HomepageSection::where('key', 'new_arrivals')->value('title'));
    }

    public function test_admin_update_flushes_cached_homepage_sections(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(HomepageSectionService::class);
        $before = $service->sections();
        $this->assertNotSame('LATEST TEAM GEAR', collect($before)->firstWhere('key', 'new_arrivals')['title'] ?? null);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.homepage.sections.update', 'new_arrivals'), [
                'title' => 'LATEST TEAM GEAR',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.homepage.sections.edit', 'new_arrivals'));

        $after = app(HomepageSectionService::class)->sections();
        $this->assertSame('LATEST TEAM GEAR', collect($after)->firstWhere('key', 'new_arrivals')['title'] ?? null);
    }

    public function test_shop_by_sport_buttons_are_user_defined_per_sport_and_can_be_removed(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $payload = [
            'title' => 'SHOP BY SPORT',
            'is_active' => '1',
            'items' => [
                [
                    'id' => 'sport-baseball',
                    'title' => 'BASEBALL',
                    'url' => '/sports/baseball',
                    'buttons' => [
                        ['id' => 'button-jersey', 'label' => 'JERSEY', 'url' => '/products?q=baseball-jersey'],
                        ['id' => 'button-accessories', 'label' => 'ACCESSORIES', 'url' => '/products?q=baseball-accessories'],
                    ],
                ],
            ],
        ];

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.homepage.sections.update', 'shop_by_sport'), $payload)
            ->assertRedirect(route('admin.homepage.sections.edit', 'shop_by_sport'));

        $stored = HomepageSection::query()->where('key', 'shop_by_sport')->firstOrFail();
        $this->assertSame('JERSEY', $stored->items[0]['buttons'][0]['label']);
        $this->assertSame('/products?q=baseball-accessories', $stored->items[0]['buttons'][1]['url']);

        $section = collect(app(HomepageSectionService::class)->sections())->firstWhere('key', 'shop_by_sport');
        $this->assertSame('ACCESSORIES', $section['items'][0]['buttons'][1]['label']);

        $payload['items'][0]['buttons'] = [];

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.homepage.sections.update', 'shop_by_sport'), $payload)
            ->assertRedirect(route('admin.homepage.sections.edit', 'shop_by_sport'));

        $stored->refresh();
        $this->assertSame([], $stored->items[0]['buttons']);
    }

    public function test_shop_by_sport_gets_four_starter_buttons_only_when_buttons_have_never_been_configured(): void
    {
        $section = HomepageSection::query()->create([
            'key' => 'shop_by_sport',
            'name' => 'Shop By Sport',
            'title' => 'SHOP BY SPORT',
            'items' => [
                [
                    'id' => 'sport-baseball',
                    'title' => 'BASEBALL',
                    'url' => '/sports/baseball',
                ],
            ],
            'settings' => ['default_sport_id' => null],
            'is_active' => true,
            'sort_order' => 30,
        ]);

        $service = app(HomepageSectionService::class);
        $service->flushCache();
        $resolved = collect($service->sections())->firstWhere('key', 'shop_by_sport');

        $this->assertSame(
            ['JERSEY', 'BOTTOMS', 'UNIFORM KITS', 'ACCESSORIES'],
            collect($resolved['items'][0]['buttons'])->pluck('label')->all(),
        );
        $this->assertArrayNotHasKey('item_button_defaults', $resolved);

        $section->items = [[
            'id' => 'sport-baseball',
            'title' => 'BASEBALL',
            'url' => '/sports/baseball',
            'buttons' => [],
        ]];
        $section->save();

        $service->flushCache();
        $resolvedAfterDelete = collect($service->sections())->firstWhere('key', 'shop_by_sport');

        $this->assertSame([], $resolvedAfterDelete['items'][0]['buttons']);
    }

    public function test_shop_by_sport_legacy_quick_links_are_retired_from_the_storefront_contract(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.homepage.sections.update', 'shop_by_sport'), [
                'title' => 'SHOP BY SPORT',
                'is_active' => '1',
                'settings' => [
                    'default_sport_id' => null,
                    'quick_links' => [
                        ['id' => 'legacy', 'label' => 'LEGACY BUTTON', 'url' => '/products?q=legacy'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.homepage.sections.edit', 'shop_by_sport'));

        $stored = HomepageSection::query()->where('key', 'shop_by_sport')->firstOrFail();
        $this->assertArrayNotHasKey('quick_links', $stored->settings ?? []);

        $section = collect(app(HomepageSectionService::class)->sections())->firstWhere('key', 'shop_by_sport');
        $this->assertArrayNotHasKey('quick_links', $section['settings']);
    }

}
