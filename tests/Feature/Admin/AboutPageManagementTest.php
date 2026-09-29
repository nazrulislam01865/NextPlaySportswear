<?php

namespace Tests\Feature\Admin;

use App\Models\AboutPageSetting;
use App\Models\User;
use App\Services\Storefront\AboutPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_manage_admin_can_update_every_about_text_url_alt_and_step_number_field(): void
    {
        $admin = $this->admin();
        $payload = $this->payload();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.about-page.update'), $payload)
            ->assertRedirect(route('admin.about-page.edit'))
            ->assertSessionHasNoErrors();

        $setting = AboutPageSetting::query()->firstOrFail();
        $this->assertSame('Hero Admin Title', $setting->hero['title']);
        $this->assertSame('Intro Admin Alt', $setting->introduction['image_alt']);
        $this->assertSame('Service 3 Admin', $setting->what_we_do['cards'][2]['title']);
        $this->assertSame('IV', $setting->how_we_work['steps'][3]['number']);
        $this->assertSame('Gallery 4 Admin Alt', $setting->gallery['items'][3]['image_alt']);
        $this->assertSame('https://example.com/bulk', $setting->cta['secondary_url']);
        $this->assertSame('Help Admin Button', $setting->help['button_label']);
        $this->assertSame('SEO Admin Description', $setting->seo['description']);

        $response = $this->get(route('about'))->assertOk();
        foreach (['Hero Admin Title', 'Intro Admin Title', 'Service 1 Admin', 'Service 2 Admin', 'Service 3 Admin', 'Process 1 Admin', 'Process 4 Admin', 'CTA Admin Title', 'Help Admin Title', 'SEO Admin Title'] as $text) {
            $response->assertSee($text);
        }
    }

    public function test_manage_admin_can_upload_and_render_all_about_images_and_icons(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $payload = array_merge($this->payload(), $this->uploadFiles('first'));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.about-page.update'), $payload)
            ->assertSessionHasNoErrors();

        $setting = AboutPageSetting::query()->firstOrFail();
        $paths = $this->storedPaths($setting);
        $this->assertCount(13, $paths);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringStartsWith('about-page/', $path);
        }

        $about = app(AboutPageService::class)->settings();
        $html = $this->get(route('about'))->assertOk()->getContent();
        $urls = [
            $about['introduction']['image_url'],
            ...array_column($about['what_we_do']['cards'], 'icon_url'),
            ...array_column($about['how_we_work']['steps'], 'icon_url'),
            ...array_column($about['gallery']['items'], 'image_url'),
            $about['help']['icon_url'],
        ];
        foreach ($urls as $url) {
            $this->assertNotNull($url);
            $this->assertStringContainsString((string) $url, $html);
        }
    }

    public function test_replacing_all_about_owned_media_deletes_only_previous_about_owned_files_after_save(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/keep.png', 'keep');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), array_merge($this->payload(), $this->uploadFiles('old')))->assertSessionHasNoErrors();
        $oldPaths = $this->storedPaths(AboutPageSetting::query()->firstOrFail());

        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), array_merge($this->payload(), $this->uploadFiles('new')))->assertSessionHasNoErrors();
        $newPaths = $this->storedPaths(AboutPageSetting::query()->firstOrFail());

        $this->assertCount(13, $oldPaths);
        $this->assertCount(13, $newPaths);
        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        foreach ($newPaths as $path) {
            Storage::disk('public')->assertExists($path);
        }
        Storage::disk('public')->assertExists('branding/keep.png');
    }

    public function test_removing_custom_media_deletes_about_owned_files_and_restores_all_defaults(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), array_merge($this->payload(), $this->uploadFiles('remove')))->assertSessionHasNoErrors();
        $oldPaths = $this->storedPaths(AboutPageSetting::query()->firstOrFail());

        $payload = $this->payload();
        $payload['remove_introduction_image'] = '1';
        $payload['remove_help_icon'] = '1';
        for ($i = 0; $i < 3; $i++) {
            $payload['remove_service_icon_'.$i] = '1';
        }
        for ($i = 0; $i < 4; $i++) {
            $payload['remove_process_icon_'.$i] = '1';
            $payload['remove_gallery_image_'.$i] = '1';
        }

        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $payload)->assertSessionHasNoErrors();

        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $setting = AboutPageSetting::query()->firstOrFail();
        $this->assertNull($setting->introduction['image_path']);
        $this->assertNull($setting->help['icon_path']);
        foreach ($setting->what_we_do['cards'] as $card) $this->assertNull($card['icon_path']);
        foreach ($setting->how_we_work['steps'] as $step) $this->assertNull($step['icon_path']);
        foreach ($setting->gallery['items'] as $item) $this->assertNull($item['image_path']);

        $about = app(AboutPageService::class)->settings();
        $this->assertStringContainsString('images/storefront/about/team-intro.webp', $about['introduction']['image_url']);
        $this->assertNull($about['what_we_do']['cards'][0]['icon_url']);
        $this->assertNull($about['help']['icon_url']);
    }

    public function test_validation_failure_keeps_current_persisted_media_and_files_unchanged(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), array_merge($this->payload(), ['introduction_image' => UploadedFile::fake()->image('current.jpg')]))->assertSessionHasNoErrors();
        $before = AboutPageSetting::query()->firstOrFail();
        $oldPath = $before->introduction['image_path'];

        $bad = $this->payload();
        $bad['cta']['primary_url'] = 'javascript:alert(1)';
        $bad['introduction_image'] = UploadedFile::fake()->image('should-not-save.jpg');
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $bad)->assertSessionHasErrors('cta.primary_url');

        $after = AboutPageSetting::query()->firstOrFail();
        $this->assertSame($oldPath, $after->introduction['image_path']);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles('about-page/introduction'));
    }

    public function test_invalid_url_or_file_input_does_not_mutate_database_or_media(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $this->payload())->assertSessionHasNoErrors();
        $before = AboutPageSetting::query()->firstOrFail()->toArray();

        $bad = $this->payload();
        $bad['help']['button_url'] = 'data:text/html,broken';
        $bad['help_icon'] = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $bad)->assertSessionHasErrors(['help.button_url', 'help_icon']);

        $this->assertSame($before['help'], AboutPageSetting::query()->firstOrFail()->toArray()['help']);
        $this->assertSame([], Storage::disk('public')->allFiles('about-page'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function payload(): array
    {
        return [
            'hero' => ['eyebrow' => 'Hero Admin Eyebrow', 'title' => 'Hero Admin Title', 'description' => 'Hero Admin Description'],
            'introduction' => ['title' => 'Intro Admin Title', 'description' => 'Intro Admin Description', 'image_alt' => 'Intro Admin Alt'],
            'what_we_do' => ['title' => 'What We Do Admin', 'cards' => [
                ['id' => 'custom-teamwear', 'title' => 'Service 1 Admin', 'description' => 'Service 1 Admin Description', 'icon_alt' => 'Service 1 Admin Alt'],
                ['id' => 'sportswear-gear', 'title' => 'Service 2 Admin', 'description' => 'Service 2 Admin Description', 'icon_alt' => 'Service 2 Admin Alt'],
                ['id' => 'bulk-orders', 'title' => 'Service 3 Admin', 'description' => 'Service 3 Admin Description', 'icon_alt' => 'Service 3 Admin Alt'],
            ]],
            'how_we_work' => ['title' => 'How We Work Admin', 'steps' => [
                ['id' => 'choose-product', 'number' => 'I', 'title' => 'Process 1 Admin', 'description' => 'Process 1 Admin Description', 'icon_alt' => 'Process 1 Admin Alt'],
                ['id' => 'personalise', 'number' => 'II', 'title' => 'Process 2 Admin', 'description' => 'Process 2 Admin Description', 'icon_alt' => 'Process 2 Admin Alt'],
                ['id' => 'review-details', 'number' => 'III', 'title' => 'Process 3 Admin', 'description' => 'Process 3 Admin Description', 'icon_alt' => 'Process 3 Admin Alt'],
                ['id' => 'place-order', 'number' => 'IV', 'title' => 'Process 4 Admin', 'description' => 'Process 4 Admin Description', 'icon_alt' => 'Process 4 Admin Alt'],
            ]],
            'gallery' => ['items' => [
                ['id' => 'team', 'image_alt' => 'Gallery 1 Admin Alt'],
                ['id' => 'fabric', 'image_alt' => 'Gallery 2 Admin Alt'],
                ['id' => 'number', 'image_alt' => 'Gallery 3 Admin Alt'],
                ['id' => 'celebration', 'image_alt' => 'Gallery 4 Admin Alt'],
            ]],
            'cta' => ['eyebrow' => 'CTA Admin Eyebrow', 'title' => 'CTA Admin Title', 'primary_label' => 'Primary Admin', 'primary_url' => '/products?admin=1', 'secondary_label' => 'Secondary Admin', 'secondary_url' => 'https://example.com/bulk'],
            'help' => ['title' => 'Help Admin Title', 'description' => 'Help Admin Description', 'icon_alt' => 'Help Admin Alt', 'button_label' => 'Help Admin Button', 'button_url' => '/contact-us?source=about'],
            'seo' => ['title' => 'SEO Admin Title', 'description' => 'SEO Admin Description'],
        ];
    }

    private function uploadFiles(string $prefix): array
    {
        $files = [
            'introduction_image' => UploadedFile::fake()->image($prefix.'-intro.jpg', 800, 400),
            'help_icon' => UploadedFile::fake()->image($prefix.'-help.png', 128, 128),
        ];
        for ($i = 0; $i < 3; $i++) {
            $files['service_icon_'.$i] = UploadedFile::fake()->image($prefix."-service-$i.png", 128, 128);
        }
        for ($i = 0; $i < 4; $i++) {
            $files['process_icon_'.$i] = UploadedFile::fake()->image($prefix."-process-$i.png", 128, 128);
            $files['gallery_image_'.$i] = UploadedFile::fake()->image($prefix."-gallery-$i.jpg", 600, 400);
        }
        return $files;
    }

    private function storedPaths(AboutPageSetting $setting): array
    {
        return array_values(array_filter([
            $setting->introduction['image_path'] ?? null,
            ...array_map(fn (array $card) => $card['icon_path'] ?? null, $setting->what_we_do['cards'] ?? []),
            ...array_map(fn (array $step) => $step['icon_path'] ?? null, $setting->how_we_work['steps'] ?? []),
            ...array_map(fn (array $item) => $item['image_path'] ?? null, $setting->gallery['items'] ?? []),
            $setting->help['icon_path'] ?? null,
        ]));
    }
}
