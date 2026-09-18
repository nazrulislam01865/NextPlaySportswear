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

}
