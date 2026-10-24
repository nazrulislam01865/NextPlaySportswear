<?php

namespace Tests\Feature\Storefront;

use App\Services\Catalog\ProductDetailIconNormalizer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDetailIconNormalizationTest extends TestCase
{
    public function test_white_background_icon_becomes_centered_transparent_and_is_idempotent(): void
    {
        if (! ProductDetailIconNormalizer::available()) {
            $this->markTestSkipped('GD is required for icon processing.');
        }

        Storage::fake('public');
        $image = imagecreatetruecolor(320, 320);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $navy = imagecolorallocate($image, 8, 33, 68);
        imagesetthickness($image, 10);
        imagerectangle($image, 72, 92, 245, 229, $navy);
        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        $original = 'storefront/product-detail-ui/white-background-icon.png';
        Storage::disk('public')->put($original, $data);
        $normalized = ProductDetailIconNormalizer::normalize($original);
        $this->assertNotSame($original, $normalized);
        $this->assertSame('png', pathinfo($normalized, PATHINFO_EXTENSION));
        $this->assertSame($normalized, ProductDetailIconNormalizer::normalize($normalized));

        $result = imagecreatefrompng(Storage::disk('public')->path($normalized));
        $this->assertSame(256, imagesx($result));
        $this->assertSame(256, imagesy($result));
        $this->assertGreaterThanOrEqual(120, (imagecolorat($result, 0, 0) >> 24) & 127);
        $this->assertLessThan(20, (imagecolorat($result, 128, 48) >> 24) & 127);
        imagedestroy($result);
    }

    public function test_svg_and_unrelated_media_are_not_modified(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('storefront/product-detail-ui/vector.svg', '<svg></svg>');
        Storage::disk('public')->put('products/example.png', 'not a product detail icon');

        $this->assertSame('storefront/product-detail-ui/vector.svg', ProductDetailIconNormalizer::normalize('storefront/product-detail-ui/vector.svg'));
        $this->assertSame('products/example.png', ProductDetailIconNormalizer::normalize('products/example.png'));
    }
}
