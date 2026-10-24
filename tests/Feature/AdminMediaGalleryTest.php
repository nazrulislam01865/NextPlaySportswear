<?php

namespace Tests\Feature;

use App\Models\MediaLibraryImage;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]), 'admin');
    }

    private function asset(int $number, string $mime = 'image/jpeg', string $category = 'general'): MediaLibraryImage
    {
        return MediaLibraryImage::query()->create([
            'name' => "Media {$number}",
            'original_filename' => "media-{$number}.jpg",
            'path' => "media-library/tests/{$number}.jpg",
            'mime_type' => $mime,
            'category' => $category,
            'size_bytes' => 2048,
            'source' => 'upload',
        ]);
    }

    public function test_gallery_limits_each_response_to_sixteen_files_and_paginates(): void
    {
        $this->login();
        foreach (range(1, 25) as $id) $this->asset($id);

        $this->getJson(route('admin.media-library.index', ['page' => 1]))
            ->assertOk()->assertJsonCount(16, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);
        $this->getJson(route('admin.media-library.index', ['page' => 2]))
            ->assertOk()->assertJsonCount(9, 'data');
    }

    public function test_filters_and_product_picker_restrict_results(): void
    {
        $this->login();
        $this->asset(1, 'image/jpeg', 'products');
        $this->asset(2, 'application/pdf', 'general');
        $this->asset(3, 'video/mp4', 'general');

        $this->getJson(route('admin.media-library.index', ['page' => 1, 'type' => 'documents']))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.type', 'documents');
        $this->getJson(route('admin.media-library.index', ['page' => 1, 'category' => 'products']))
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson(route('admin.media-library.index', ['page' => 1, 'purpose' => 'product']))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.type', 'images');
    }

    public function test_media_details_can_be_updated_without_changing_original_file(): void
    {
        $this->login();
        $file = $this->asset(5);
        $this->patchJson(route('admin.media-library.update', $file), [
            'alt_text' => 'Custom basketball uniform',
            'caption' => 'Black and orange jersey',
            'description' => 'Product gallery image',
            'category' => 'products',
        ])->assertOk()->assertJsonPath('data.caption', 'Black and orange jersey');

        $this->assertDatabaseHas('media_library_images', [
            'id' => $file->id,
            'category' => 'products',
            'path' => 'media-library/tests/5.jpg',
        ]);
    }

    public function test_delete_preserves_underlying_asset_used_by_content(): void
    {
        Storage::fake('public');
        $this->login();
        $file = $this->asset(6);
        Storage::disk('public')->put('media-library/tests/6.jpg', 'image bytes');

        $this->deleteJson(route('admin.media-library.destroy', $file))->assertOk();
        $this->assertDatabaseMissing('media_library_images', ['id' => $file->id]);
        Storage::disk('public')->assertExists('media-library/tests/6.jpg');
    }

    public function test_product_linked_media_cannot_be_deleted(): void
    {
        $this->login();
        $file = $this->asset(7);
        $product = Product::query()->create([
            'name' => 'Basketball Jersey', 'slug' => 'basketball-jersey', 'sku' => 'MEDIA-TEST-1',
            'status' => 'active', 'base_price' => 10, 'currency' => 'USD',
            'minimum_quantity' => 1, 'is_customizable' => true, 'is_active' => true,
        ]);
        ProductImage::query()->create([
            'product_id' => $product->id, 'media_library_image_id' => $file->id,
            'path' => $file->path, 'is_primary' => true, 'sort_order' => 0,
        ]);

        $this->deleteJson(route('admin.media-library.destroy', $file))->assertStatus(409);
        $this->assertDatabaseHas('media_library_images', ['id' => $file->id]);
    }
}
