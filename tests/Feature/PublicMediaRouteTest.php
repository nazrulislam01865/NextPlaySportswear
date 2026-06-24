<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaRouteTest extends TestCase
{
    public function test_public_image_is_streamed_without_a_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('catalog/example.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        $response = $this->get('/media/catalog/example.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_non_image_public_files_are_not_exposed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('catalog/private.txt', 'not an image');

        $this->get('/media/catalog/private.txt')->assertNotFound();
    }
}
