<?php

namespace Tests\Feature\Admin;

use App\Models\HomepageSection;
use App\Models\User;
use App\Services\Catalog\HomepageSectionMediaService;
use App\Services\Storefront\HomepageSectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageSectionMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_upload_is_stored_under_the_section_and_stable_item_id(): void
    {
        Storage::fake('public');
        $section = HomepageSection::query()->create([
            'key' => 'audience', 'name' => 'Audience Tiles',
            'items' => [['id' => 'men', 'title' => 'MEN'], ['id' => 'women', 'title' => 'WOMEN'], ['id' => 'kids', 'title' => 'KIDS']],
            'is_active' => true, 'sort_order' => 20,
        ]);
        $request = Request::create('/', 'POST', ['items' => [['id' => 'men', 'title' => 'MEN'], ['id' => 'women', 'title' => 'WOMEN'], ['id' => 'kids', 'title' => 'KIDS']]], [], [
            'items' => [['image_file' => UploadedFile::fake()->image('men.webp', 1200, 1200)]],
        ]);

        app(HomepageSectionMediaService::class)->sync($section, $request);
        $section->refresh();

        $path = $section->items[0]['image_path'];
        $this->assertStringStartsWith('homepage/sections/audience/items/men/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_item_media_replacement_and_removal_only_delete_owned_item_files(): void
    {
        Storage::fake('public');
        $old = 'homepage/sections/audience/items/men/old.webp';
        $foreign = 'homepage/sections/another/items/men/keep.webp';
        Storage::disk('public')->put($old, 'old');
        Storage::disk('public')->put($foreign, 'keep');
        $section = HomepageSection::query()->create([
            'key' => 'audience', 'name' => 'Audience Tiles',
            'items' => [['id' => 'men', 'title' => 'MEN', 'image_path' => $old], ['id' => 'women', 'title' => 'WOMEN'], ['id' => 'kids', 'title' => 'KIDS']],
            'is_active' => true, 'sort_order' => 20,
        ]);

        $request = Request::create('/', 'POST', ['items' => [
            ['id' => 'women', 'title' => 'WOMEN'],
            ['id' => 'kids', 'title' => 'KIDS'],
        ]]);
        app(HomepageSectionMediaService::class)->sync($section, $request);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($foreign);
    }
    public function test_item_image_update_flushes_cached_homepage_section_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(HomepageSectionService::class);
        $before = collect($service->sections())->firstWhere('key', 'audience');
        $this->assertEmpty(collect($before['items'] ?? [])->firstWhere('id', 'men')['image'] ?? null);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.homepage.sections.update', 'audience'), [
                'is_active' => '1',
                'items' => [
                    ['id' => 'men', 'title' => 'MEN', 'image_file' => UploadedFile::fake()->image('men.webp', 1344, 854)],
                    ['id' => 'women', 'title' => 'WOMEN'],
                    ['id' => 'kids', 'title' => 'KIDS'],
                ],
            ])
            ->assertRedirect(route('admin.homepage.sections.edit', 'audience'));

        $after = collect(app(HomepageSectionService::class)->sections())->firstWhere('key', 'audience');
        $men = collect($after['items'] ?? [])->firstWhere('id', 'men');
        $this->assertNotEmpty($men['image'] ?? null);
        $this->assertStringContainsString('/homepage/sections/audience/items/men/', (string) ($men['image'] ?? ''));
    }

    public function test_homepage_item_upload_rejects_a_wrong_aspect_ratio_with_a_clear_message(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.homepage.sections.edit', 'audience'))
            ->patch(route('admin.homepage.sections.update', 'audience'), [
                'is_active' => '1',
                'items' => [
                    ['id' => 'men', 'title' => 'MEN', 'image_file' => UploadedFile::fake()->image('men.webp', 1200, 1200)],
                    ['id' => 'women', 'title' => 'WOMEN'],
                    ['id' => 'kids', 'title' => 'KIDS'],
                ],
            ]);

        $response->assertRedirect(route('admin.homepage.sections.edit', 'audience'));
        $response->assertSessionHasErrors('items.0.image_file');
        $this->assertStringContainsString(
            '672:427 aspect ratio',
            (string) session('errors')->first('items.0.image_file')
        );
    }


}
