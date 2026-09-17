<?php

namespace Tests\Unit;

use App\Support\PublicMedia;
use Tests\TestCase;

class PublicMediaTest extends TestCase
{
    public function test_it_builds_request_relative_urls_for_public_disk_paths(): void
    {
        $this->assertSame(
            '/media/catalog/products/front%20view.jpg',
            PublicMedia::url('catalog/products/front view.jpg')
        );
    }

    public function test_it_repairs_legacy_local_storage_urls(): void
    {
        $this->assertSame(
            '/media/products/9/front.jpg',
            PublicMedia::url(null, 'http://localhost/storage/products/9/front.jpg')
        );
    }

    public function test_it_preserves_real_remote_urls(): void
    {
        $this->assertSame(
            'https://cdn.example.com/products/front.jpg',
            PublicMedia::url(null, 'https://cdn.example.com/products/front.jpg')
        );
    }
}
