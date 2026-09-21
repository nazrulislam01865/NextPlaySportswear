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
    public function test_same_application_absolute_storage_base_is_forced_through_media_route(): void
    {
        config()->set('app.url', 'https://shop.example.test');
        config()->set('filesystems.disks.public.url', 'https://shop.example.test/storage');

        $this->assertSame(
            '/media/products/9/front.jpg',
            PublicMedia::url('products/9/front.jpg')
        );
    }

    public function test_it_normalizes_legacy_relative_storage_url_even_when_public_disk_uses_media(): void
    {
        config()->set('filesystems.disks.public.url', '/media');

        $this->assertSame(
            '/media/homepage/sections/banner.jpg',
            PublicMedia::url(null, '/storage/homepage/sections/banner.jpg')
        );
    }

}
