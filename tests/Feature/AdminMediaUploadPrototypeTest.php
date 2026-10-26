<?php

namespace Tests\Feature;

use App\Models\MediaLibraryImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaUploadPrototypeTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]), 'admin');
    }

    public function test_upload_screen_renders_new_queue_without_preloading_gallery(): void
    {
        $this->login();

        $this->get(route('admin.media-library.upload'))
            ->assertOk()
            ->assertSee('Drag &amp; drop files here', false)
            ->assertSee('File Preview &amp; Details', false)
            ->assertDontSee("Today's Uploaded Media");
    }

    public function test_standalone_upload_saves_editable_metadata_in_single_request(): void
    {
        Storage::fake('public');
        $this->login();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/S9sAAAAASUVORK5CYII=');
        $response = $this->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->createWithContent('basketball-jersey.png', $png)],
            'metadata' => [[
                'title' => 'Basketball Jersey Black',
                'alt_text' => 'NextPlay basketball jersey black',
                'caption' => 'Black basketball jersey',
                'description' => 'High-quality custom basketball jersey',
            ]],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Basketball Jersey Black')
            ->assertJsonPath('data.0.alt_text', 'NextPlay basketball jersey black')
            ->assertJsonPath('data.0.caption', 'Black basketball jersey');

        $asset = MediaLibraryImage::query()->firstOrFail();
        $this->assertSame('High-quality custom basketball jersey', $asset->description);
        $this->assertSame('basketball-jersey.png', $asset->original_filename);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_upload_rejects_more_than_twenty_files(): void
    {
        Storage::fake('public');
        $this->login();
        $files = [];
        foreach (range(1, 21) as $index) {
            $files[] = UploadedFile::fake()->create("file-{$index}.pdf", 2, 'application/pdf');
        }
        $this->post(route('admin.media-library.store'), ['files' => $files], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['files']);
        $this->assertDatabaseCount('media_library_images', 0);
    }

    public function test_product_picker_remains_image_only(): void
    {
        Storage::fake('public');
        $this->login();
        $this->post(route('admin.media-library.store'), [
            'images' => [UploadedFile::fake()->create('unsupported.pdf', 4, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('media_library_images', 0);
    }
}
