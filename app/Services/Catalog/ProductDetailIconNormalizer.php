<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Storage;

/**
 * Crop empty canvas from centralized product-detail UI icon uploads.
 * Only application-owned icons are considered; other product images are untouched.
 * If GD is unavailable, the original upload is safely retained.
 */
final class ProductDetailIconNormalizer
{
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

        if ($decoder === null || ! function_exists($decoder)) {
            return $path;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return $path;
        }

        $source = null;
        $cropped = null;
        try {
            $absolute = $disk->path($path);
            $dimensions = @getimagesize($absolute);
            if (! $dimensions) {
                return $path;
            }

            [$width, $height] = $dimensions;
            if ($width < 24 || $height < 24 || $width * $height > 6000000) {
                return $path;
            }

            $source = @$decoder($absolute);
            if (! $source) {
                return $path;
            }

            if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($source)) {
                imagepalettetotruecolor($source);
            }

            $corners = [[0, 0], [$width - 1, 0], [0, $height - 1], [$width - 1, $height - 1]];
            $transparent = true;
            $white = true;
            foreach ($corners as [$x, $y]) {
                $pixel = imagecolorat($source, $x, $y);
                $alpha = ($pixel >> 24) & 127;
                $transparent = $transparent && $alpha >= 110;
                $white = $white && ($alpha >= 110 || (
                    (($pixel >> 16) & 255) >= 246 && (($pixel >> 8) & 255) >= 246 && ($pixel & 255) >= 246
                ));
            }

            // Do not crop photographs, filled-background icons, or graphics with artwork at the edges.
            if (! $transparent && ! $white) {
                return $path;
            }

            $left = $width;
            $top = $height;
            $right = -1;
            $bottom = -1;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel = imagecolorat($source, $x, $y);
                    $alpha = ($pixel >> 24) & 127;
                    $visible = $transparent
                        ? $alpha < 110
                        : $alpha < 110 && (
                            (($pixel >> 16) & 255) < 238 || (($pixel >> 8) & 255) < 238 || ($pixel & 255) < 238
                        );
                    if (! $visible) {
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

            $contentSize = max($right - $left + 1, $bottom - $top + 1);
            $padding = max(2, (int) round($contentSize * 0.06));
            $left = max(0, $left - $padding);
            $top = max(0, $top - $padding);
            $right = min($width - 1, $right + $padding);
            $bottom = min($height - 1, $bottom + $padding);
            $newWidth = $right - $left + 1;
            $newHeight = $bottom - $top + 1;

            // An almost full-bleed image needs no new file (or unnecessary recompression).
            if ($newWidth >= $width * 0.93 && $newHeight >= $height * 0.93) {
                return $path;
            }

            $cropped = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
            imagecopy($cropped, $source, 0, 0, $left, $top, $newWidth, $newHeight);

            // White-background icons (including the supplied 800x800 PNGs) should
            // not show an opaque white square against tinted storefront panels.
            if (! $transparent) {
                for ($y = 0; $y < $newHeight; $y++) {
                    for ($x = 0; $x < $newWidth; $x++) {
                        $pixel = imagecolorat($cropped, $x, $y);
                        $r = ($pixel >> 16) & 255;
                        $g = ($pixel >> 8) & 255;
                        $b = $pixel & 255;
                        $contrast = 255 - min($r, $g, $b);
                        $opacity = max(0, min(1, ($contrast - 6) / 32));
                        $alpha = max(($pixel >> 24) & 127, (int) round(127 * (1 - $opacity)));
                        imagesetpixel($cropped, $x, $y, ($alpha << 24) | ($r << 16) | ($g << 8) | $b);
                    }
                }
            }

            ob_start();
            $written = imagepng($cropped, null, 6);
            $bytes = ob_get_clean();
            if (! $written || ! is_string($bytes) || $bytes === '') {
                return $path;
            }

            $newPath = 'storefront/product-detail-ui/'.bin2hex(random_bytes(20)).'.png';
            if (! $disk->put($newPath, $bytes)) {
                return $path;
            }

            return $newPath;
        } catch (\Throwable $exception) {
            report($exception);
            return $path;
        } finally {
            if ($cropped instanceof \GdImage) {
                imagedestroy($cropped);
            }
            if ($source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }
}
