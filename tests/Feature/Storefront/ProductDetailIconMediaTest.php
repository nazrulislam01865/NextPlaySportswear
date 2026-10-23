<?php

namespace Tests\Feature\Storefront;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDetailIconMediaTest extends TestCase
{
    public function test_product_detail_icon_svg_is_served_as_a_sandboxed_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'storefront/product-detail-ui/icon.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 2h20v20H2z"/></svg>'
        );

        $this->get('/media/storefront/product-detail-ui/icon.svg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }

    public function test_unrelated_svg_uploads_stay_blocked(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('other/untrusted.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->get('/media/other/untrusted.svg')->assertNotFound();
    }
}
