<?php

namespace Tests\Feature\Storefront;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PromotionBannerResponsiveMediaTest extends TestCase
{
    public function test_mobile_image_is_rendered_as_picture_source_when_present(): void
    {
        $html = Blade::render('<x-storefront.sale-banner :banner="$banner" />', [
            'banner' => $this->payload('/media/desktop.jpg', '/media/mobile.jpg'),
        ]);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('media="(max-width: 640px)"', $html);
        $this->assertStringContainsString('srcset="/media/mobile.jpg"', $html);
        $this->assertStringContainsString('src="/media/desktop.jpg"', $html);
    }

    public function test_desktop_image_remains_img_fallback_when_mobile_is_absent(): void
    {
        $html = Blade::render('<x-storefront.sale-banner :banner="$banner" />', [
            'banner' => $this->payload('/media/desktop.jpg', null),
        ]);

        $this->assertStringNotContainsString('<source', $html);
        $this->assertStringContainsString('src="/media/desktop.jpg"', $html);
    }

    private function payload(string $desktop, ?string $mobile): array
    {
        return [
            'name' => 'Promotion',
            'desktop_image_url' => $desktop,
            'mobile_image_url' => $mobile,
            'alt_text' => 'Promotion',
            'heading' => 'Promotion',
            'cta_label' => 'Shop',
            'destination_link' => '/products',
        ];
    }
}
