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
        if (! self::available() || ! self::isIconDirectory($path)) {
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

            // Inspect the visible bounds first: older normalized uploads may
            // have an opaque light tile *inside* a transparent square canvas.
            $left = $width;
            $top = $height;
            $right = -1;
            $bottom = -1;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel = imagecolorat($source, $x, $y);
                    if (self::originalOpacity($pixel) < 0.12) {
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

            $tileColor = self::flatLightBackground($source, $left, $top, $right, $bottom);
            $hasFlatTile = $tileColor !== null;
            if ($hasFlatTile) {
                // Ignore uniform background while finding real artwork bounds.
                $left = $width;
                $top = $height;
                $right = -1;
                $bottom = -1;
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $pixel = imagecolorat($source, $x, $y);
                        if (self::iconOpacity($pixel, self::originalOpacity($pixel), $tileColor) < 0.12) {
                            continue;
                        }
                        $left = min($left, $x);
                        $right = max($right, $x);
                        $top = min($top, $y);
                        $bottom = max($bottom, $y);
                    }
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
            if (! $hasFlatTile && $width === self::CANVAS && $height === self::CANVAS
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
            if ($hasFlatTile) {
                for ($y = $top; $y <= $bottom; $y++) {
                    for ($x = $left; $x <= $right; $x++) {
                        $pixel = imagecolorat($source, $x, $y);
                        $opacity = self::iconOpacity($pixel, self::originalOpacity($pixel), $tileColor);
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

            $newPath = dirname($path).'/'.bin2hex(random_bytes(20)).'.png';
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

    private static function isIconDirectory(string $path): bool
    {
        return str_starts_with($path, 'storefront/product-detail-ui/')
            || str_starts_with($path, 'master-data/shipping-methods/');
    }

    private static function originalOpacity(int $pixel): float
    {
        return 1 - ((($pixel >> 24) & 127) / 127);
    }

    /** @return array{int, int, int}|null */
    private static function flatLightBackground(\GdImage $source, int $left, int $top, int $right, int $bottom): ?array
    {
        if (($right - $left) < 24 || ($bottom - $top) < 24) {
            return null;
        }

        $corners = [
            imagecolorat($source, $left, $top),
            imagecolorat($source, $right, $top),
            imagecolorat($source, $left, $bottom),
            imagecolorat($source, $right, $bottom),
        ];
        $colors = [];
        foreach ($corners as $pixel) {
            if (self::originalOpacity($pixel) < 0.9) {
                return null;
            }
            $rgb = [($pixel >> 16) & 255, ($pixel >> 8) & 255, $pixel & 255];
            if (min($rgb) < 215 || max($rgb) - min($rgb) > 32) {
                return null;
            }
            $colors[] = $rgb;
        }
        $color = [
            (int) round(array_sum(array_column($colors, 0)) / 4),
            (int) round(array_sum(array_column($colors, 1)) / 4),
            (int) round(array_sum(array_column($colors, 2)) / 4),
        ];
        foreach ($colors as $rgb) {
            if (self::distance($rgb, $color) > 16) {
                return null;
            }
        }

        // A uniform light border distinguishes an icon tile from a photo or
        // an icon whose artwork genuinely reaches the outer edge.
        $edge = 0;
        $matches = 0;
        for ($x = $left; $x <= $right; $x += max(1, intdiv($right - $left, 64))) {
            foreach ([$top, $bottom] as $y) {
                $edge++;
                $pixel = imagecolorat($source, $x, $y);
                if (self::originalOpacity($pixel) > 0.9 && self::distance(self::rgb($pixel), $color) <= 24) {
                    $matches++;
                }
            }
        }
        for ($y = $top; $y <= $bottom; $y += max(1, intdiv($bottom - $top, 64))) {
            foreach ([$left, $right] as $x) {
                $edge++;
                $pixel = imagecolorat($source, $x, $y);
                if (self::originalOpacity($pixel) > 0.9 && self::distance(self::rgb($pixel), $color) <= 24) {
                    $matches++;
                }
            }
        }
        if ($edge === 0 || $matches / $edge < 0.85) {
            return null;
        }

        $contrast = 0;
        for ($y = $top; $y <= $bottom; $y += max(1, intdiv($bottom - $top, 18))) {
            for ($x = $left; $x <= $right; $x += max(1, intdiv($right - $left, 18))) {
                $pixel = imagecolorat($source, $x, $y);
                if (self::originalOpacity($pixel) > 0.5 && self::distance(self::rgb($pixel), $color) > 80) {
                    $contrast++;
                }
            }
        }
        return $contrast >= 8 ? $color : null;
    }

    /** @return array{int, int, int} */
    private static function rgb(int $pixel): array
    {
        return [($pixel >> 16) & 255, ($pixel >> 8) & 255, $pixel & 255];
    }

    /** @param array{int, int, int} $a @param array{int, int, int} $b */
    private static function distance(array $a, array $b): int
    {
        return max(abs($a[0] - $b[0]), abs($a[1] - $b[1]), abs($a[2] - $b[2]));
    }

    /** @param array{int, int, int}|null $tileColor */
    private static function iconOpacity(int $pixel, float $originalOpacity, ?array $tileColor): float
    {
        if ($tileColor === null) {
            return $originalOpacity;
        }
        $distance = self::distance(self::rgb($pixel), $tileColor);
        return $originalOpacity * max(0, min(1, ($distance - 7) / 30));
    }
}
