<?php

namespace Tests\Unit\Services\Catalog;

use App\Http\Requests\Admin\AboutPageRequest;
use App\Services\Catalog\AboutPageMediaService;
use App\Services\Storefront\AboutPageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageMediaServiceTest extends TestCase
{
    public function test_new_uploads_use_dedicated_about_page_directories(): void
    {
        Storage::fake('public');
        $current = app(AboutPageService::class)->defaults();
        $request = $this->requestWithFiles([
            'introduction_image' => UploadedFile::fake()->image('intro.jpg'),
            'service_icon_0' => UploadedFile::fake()->image('service.png'),
            'process_icon_0' => UploadedFile::fake()->image('process.png'),
            'gallery_image_0' => UploadedFile::fake()->image('gallery.jpg'),
            'help_icon' => UploadedFile::fake()->image('help.png'),
        ]);

        $mutation = app(AboutPageMediaService::class)->prepare($request, $current, $this->validatedPayload());

        $this->assertStringStartsWith('about-page/introduction/', $mutation['payload']['introduction']['image_path']);
        $this->assertStringStartsWith('about-page/what-we-do/icons/', $mutation['payload']['what_we_do']['cards'][0]['icon_path']);
        $this->assertStringStartsWith('about-page/how-we-work/icons/', $mutation['payload']['how_we_work']['steps'][0]['icon_path']);
        $this->assertStringStartsWith('about-page/gallery/', $mutation['payload']['gallery']['items'][0]['image_path']);
        $this->assertStringStartsWith('about-page/help/icons/', $mutation['payload']['help']['icon_path']);
        $this->assertCount(5, $mutation['new_paths']);
    }

    public function test_no_upload_keeps_existing_custom_path(): void
    {
        Storage::fake('public');
        $current = app(AboutPageService::class)->defaults();
        $current['introduction']['image_path'] = 'about-page/introduction/existing.jpg';

        $mutation = app(AboutPageMediaService::class)->prepare($this->requestWithFiles([]), $current, $this->validatedPayload());

        $this->assertSame('about-page/introduction/existing.jpg', $mutation['payload']['introduction']['image_path']);
        $this->assertSame([], $mutation['new_paths']);
        $this->assertSame([], $mutation['delete_after_commit']);
    }

    public function test_replace_stages_new_file_without_deleting_previous_file_before_commit_cleanup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('about-page/introduction/old.jpg', 'old');
        $current = app(AboutPageService::class)->defaults();
        $current['introduction']['image_path'] = 'about-page/introduction/old.jpg';

        $mutation = app(AboutPageMediaService::class)->prepare(
            $this->requestWithFiles(['introduction_image' => UploadedFile::fake()->image('new.jpg')]),
            $current,
            $this->validatedPayload()
        );

        Storage::disk('public')->assertExists('about-page/introduction/old.jpg');
        Storage::disk('public')->assertExists($mutation['payload']['introduction']['image_path']);
        $this->assertContains('about-page/introduction/old.jpg', $mutation['delete_after_commit']);
    }

    public function test_commit_cleanup_deletes_only_replaced_or_removed_about_owned_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('about-page/introduction/old.jpg', 'old');
        Storage::disk('public')->put('branding/logo.png', 'brand');

        app(AboutPageMediaService::class)->commitCleanup([
            'payload' => [],
            'new_paths' => [],
            'delete_after_commit' => ['about-page/introduction/old.jpg', 'branding/logo.png', 'images/storefront/about/team-intro.webp'],
        ]);

        Storage::disk('public')->assertMissing('about-page/introduction/old.jpg');
        Storage::disk('public')->assertExists('branding/logo.png');
    }

    public function test_rollback_deletes_only_newly_staged_files_and_preserves_previous_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('about-page/gallery/old.jpg', 'old');
        Storage::disk('public')->put('about-page/gallery/new.jpg', 'new');

        app(AboutPageMediaService::class)->rollback([
            'payload' => [],
            'new_paths' => ['about-page/gallery/new.jpg'],
            'delete_after_commit' => ['about-page/gallery/old.jpg'],
        ]);

        Storage::disk('public')->assertMissing('about-page/gallery/new.jpg');
        Storage::disk('public')->assertExists('about-page/gallery/old.jpg');
    }

    public function test_remove_custom_media_clears_reference_and_restores_fallback_contract(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('about-page/help/icons/old.png', 'old');
        $current = app(AboutPageService::class)->defaults();
        $current['help']['icon_path'] = 'about-page/help/icons/old.png';
        $request = $this->requestWithFiles([], ['remove_help_icon' => '1']);

        $mutation = app(AboutPageMediaService::class)->prepare($request, $current, $this->validatedPayload());

        $this->assertNull($mutation['payload']['help']['icon_path']);
        $this->assertContains('about-page/help/icons/old.png', $mutation['delete_after_commit']);
        Storage::disk('public')->assertExists('about-page/help/icons/old.png');
    }

    public function test_cleanup_refuses_paths_outside_about_page_root(): void
    {
        $service = app(AboutPageMediaService::class);

        $this->assertTrue($service->isAboutOwnedPath('about-page/gallery/file.webp'));
        $this->assertFalse($service->isAboutOwnedPath('branding/file.webp'));
        $this->assertFalse($service->isAboutOwnedPath('../about-page/file.webp'));
        $this->assertFalse($service->isAboutOwnedPath('about-page/../branding/file.webp'));
        $this->assertFalse($service->isAboutOwnedPath('/images/storefront/about/file.webp'));
    }

    public function test_shipped_public_about_assets_are_never_storage_delete_candidates(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/storefront/about/team-intro.webp', 'should stay');

        app(AboutPageMediaService::class)->commitCleanup([
            'payload' => [],
            'new_paths' => [],
            'delete_after_commit' => ['images/storefront/about/team-intro.webp'],
        ]);

        Storage::disk('public')->assertExists('images/storefront/about/team-intro.webp');
    }

    private function requestWithFiles(array $files, array $input = []): AboutPageRequest
    {
        $request = AboutPageRequest::create('/admin/about-page', 'PUT', $input, [], $files);

        return $request;
    }

    private function validatedPayload(): array
    {
        $defaults = app(AboutPageService::class)->defaults();

        unset($defaults['introduction']['image_path'], $defaults['introduction']['fallback_asset']);
        foreach ($defaults['what_we_do']['cards'] as &$card) {
            unset($card['icon_path'], $card['fallback_icon']);
        }
        unset($card);
        foreach ($defaults['how_we_work']['steps'] as &$step) {
            unset($step['icon_path'], $step['fallback_icon']);
        }
        unset($step);
        foreach ($defaults['gallery']['items'] as &$item) {
            unset($item['image_path'], $item['fallback_asset']);
        }
        unset($item);
        unset($defaults['help']['icon_path'], $defaults['help']['fallback_icon']);

        return $defaults;
    }
}
