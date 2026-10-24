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

    public function test_light_blue_tile_is_removed_from_shipping_method_icon(): void
    {
        if (! ProductDetailIconNormalizer::available()) {
            $this->markTestSkipped('GD is required for icon processing.');
        }

        Storage::fake('public');
        $source = imagecreatetruecolor(220, 180);
        imagefill($source, 0, 0, imagecolorallocate($source, 237, 244, 250));
        $navy = imagecolorallocate($source, 9, 35, 71);
        imagefilledrectangle($source, 58, 42, 162, 132, $navy);
        ob_start();
        imagepng($source);
        $bytes = ob_get_clean();
        imagedestroy($source);

        $path = 'master-data/shipping-methods/light-blue-tile.png';
        Storage::disk('public')->put($path, $bytes);
        $resultPath = ProductDetailIconNormalizer::normalize($path);
        $this->assertNotSame($path, $resultPath);
        $this->assertStringStartsWith('master-data/shipping-methods/', $resultPath);
        $result = imagecreatefrompng(Storage::disk('public')->path($resultPath));
        $this->assertGreaterThan(120, (imagecolorat($result, 20, 20) >> 24) & 127);
        $this->assertLessThan(10, (imagecolorat($result, 128, 128) >> 24) & 127);
        imagedestroy($result);
        $this->assertSame($resultPath, ProductDetailIconNormalizer::normalize($resultPath));
    }

    public function test_embedded_light_tile_inside_transparent_canvas_is_removed(): void
    {
        if (! ProductDetailIconNormalizer::available()) {
            $this->markTestSkipped('GD is required for icon processing.');
        }

        Storage::fake('public');
        $source = imagecreatetruecolor(256, 256);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagefill($source, 0, 0, imagecolorallocatealpha($source, 0, 0, 0, 127));
        imagefilledrectangle($source, 24, 24, 231, 231, imagecolorallocatealpha($source, 241, 244, 247, 0));
        imagefilledrectangle($source, 70, 65, 180, 175, imagecolorallocatealpha($source, 12, 36, 70, 0));
        ob_start();
        imagepng($source);
        $bytes = ob_get_clean();
        imagedestroy($source);

        $path = 'storefront/product-detail-ui/inset-gray-tile.png';
        Storage::disk('public')->put($path, $bytes);
        $resultPath = ProductDetailIconNormalizer::normalize($path);
        $this->assertNotSame($path, $resultPath);
        $result = imagecreatefrompng(Storage::disk('public')->path($resultPath));
        $this->assertGreaterThan(120, (imagecolorat($result, 25, 25) >> 24) & 127);
        $this->assertLessThan(10, (imagecolorat($result, 128, 128) >> 24) & 127);
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
