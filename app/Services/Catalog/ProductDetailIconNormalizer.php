<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Storage;

/**
 * Give uploaded product-detail icons a common transparent square canvas.
 * Artwork keeps its original proportions, without backgrounds or frames.
 * Product photographs and files outside the dedicated icon directory are untouched.
 */
final class ProductDetailIconNormalizer
{
    private const CANVAS = 256;
    private const ARTWORK = 208;

    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    public static function normalize(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (! self::available() || ! str_starts_with($path, 'storefront/product-detail-ui/')) {
            return $path;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $decoder = match ($extension) {
            'png' => 'imagecreatefrompng',
            'jpg', 'jpeg' => 'imagecreatefromjpeg',
            'webp' => 'imagecreatefromwebp',
            'avif' => 'imagecreatefromavif',
            default => null,
        };

        // SVGs remain untouched (including their viewBox and embedded artwork).
        if ($decoder === null || ! function_exists($decoder)) {
            return $path;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return $path;
        }

        $source = null;
        $canvas = null;
        try {
            $file = $disk->path($path);
            $dimensions = @getimagesize($file);
            if (! $dimensions) {
                return $path;
            }
            [$width, $height] = $dimensions;
            if ($width < 24 || $height < 24 || $width * $height > 6000000) {
                return $path;
            }

            $source = @$decoder($file);
            if (! $source) {
                return $path;
            }
            if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($source)) {
                imagepalettetotruecolor($source);
            }

            $opaquePixels = 0;
            $whitePixels = 0;
            $darkPixels = 0;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel = imagecolorat($source, $x, $y);
                    if ((($pixel >> 24) & 127) > 20) {
                        continue;
                    }
                    $opaquePixels++;
                    $r = ($pixel >> 16) & 255;
                    $g = ($pixel >> 8) & 255;
                    $b = $pixel & 255;
                    if (min($r, $g, $b) >= 246) {
                        $whitePixels++;
                    }
                    if (min($r, $g, $b) < 220) {
                        $darkPixels++;
                    }
                }
            }

            // Remove an embedded white tile only when the image is clearly
            // dark icon artwork on white, not a photo or a white-colored logo.
            $hasWhiteTile = $opaquePixels > 0
                && $whitePixels / $opaquePixels > 0.65
                && $darkPixels / $opaquePixels > 0.01;

            $left = $width;
            $top = $height;
            $right = -1;
            $bottom = -1;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel = imagecolorat($source, $x, $y);
                    $originalOpacity = 1 - ((($pixel >> 24) & 127) / 127);
                    $opacity = self::iconOpacity($pixel, $originalOpacity, $hasWhiteTile);
                    if ($opacity < 0.12) {
                        continue;
                    }
                    $left = min($left, $x);
                    $right = max($right, $x);
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }

            if ($right < $left || $bottom < $top) {
                return $path;
            }

            $artWidth = $right - $left + 1;
            $artHeight = $bottom - $top + 1;
            $artSize = max($artWidth, $artHeight);
            $centerX = ($left + $right) / 2;
            $centerY = ($top + $bottom) / 2;

            // An already standardized icon must not be recompressed with every
            // admin save or repair command. This also makes the command rerunnable.
            if (! $hasWhiteTile && $width === self::CANVAS && $height === self::CANVAS
                && abs($artSize - self::ARTWORK) <= 3
                && abs($centerX - (self::CANVAS - 1) / 2) <= 3
                && abs($centerY - (self::CANVAS - 1) / 2) <= 3) {
                return $path;
            }

            $canvas = imagecreatetruecolor(self::CANVAS, self::CANVAS);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

            $scale = self::ARTWORK / $artSize;
            $scaledWidth = max(1, (int) round($artWidth * $scale));
            $scaledHeight = max(1, (int) round($artHeight * $scale));
            $offsetX = (int) floor((self::CANVAS - $scaledWidth) / 2);
            $offsetY = (int) floor((self::CANVAS - $scaledHeight) / 2);

            // Resample onto a separate transparent source only when needed.
            // This preserves anti-aliased edges and the uploaded navy artwork.
            if ($hasWhiteTile) {
                for ($y = $top; $y <= $bottom; $y++) {
                    for ($x = $left; $x <= $right; $x++) {
                        $pixel = imagecolorat($source, $x, $y);
                        $originalOpacity = 1 - ((($pixel >> 24) & 127) / 127);
                        $opacity = self::iconOpacity($pixel, $originalOpacity, true);
                        $alpha = (int) round(127 * (1 - $opacity));
                        imagesetpixel($source, $x, $y, ($alpha << 24) | ($pixel & 0xffffff));
                    }
                }
            }

            imagecopyresampled(
                $canvas, $source,
                $offsetX, $offsetY, $left, $top,
                $scaledWidth, $scaledHeight, $artWidth, $artHeight
            );

            ob_start();
            $success = imagepng($canvas, null, 6);
            $bytes = ob_get_clean();
            if (! $success || ! is_string($bytes) || $bytes === '') {
                return $path;
            }

            $newPath = 'storefront/product-detail-ui/'.bin2hex(random_bytes(20)).'.png';
            return $disk->put($newPath, $bytes) ? $newPath : $path;
        } catch (\Throwable $exception) {
            report($exception);
            return $path;
        } finally {
            if ($canvas instanceof \GdImage) {
                imagedestroy($canvas);
            }
            if ($source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }

    private static function iconOpacity(int $pixel, float $originalOpacity, bool $hasWhiteTile): float
    {
        if (! $hasWhiteTile) {
            return $originalOpacity;
        }

        $r = ($pixel >> 16) & 255;
        $g = ($pixel >> 8) & 255;
        $b = $pixel & 255;
        $contrast = 255 - min($r, $g, $b);
        return $originalOpacity * max(0, min(1, ($contrast - 6) / 32));
    }
}
