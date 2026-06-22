<?php

namespace App\Services\Storefront;

use App\Models\HomepageSlide;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

class HomepageSliderService
{
    private const CACHE_KEY = 'storefront.homepage-slider.v1';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $runtimeSlides = null;

    /** @return array<int, array<string, mixed>> */
    public function slides(): array
    {
        if ($this->runtimeSlides !== null) {
            return $this->runtimeSlides;
        }

        $ttl = max(1, (int) config('storefront.slider_cache_seconds', 600));

        try {
            $payload = Cache::remember(self::CACHE_KEY, $ttl, fn (): array => $this->buildPayload());

            if (! is_array($payload)) {
                Cache::forget(self::CACHE_KEY);
                $payload = $this->buildPayload();
                Cache::put(self::CACHE_KEY, $payload, $ttl);
            }
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'homepage_slides')) {
                throw $exception;
            }

            return $this->runtimeSlides = $this->legacySlides();
        }

        return $this->runtimeSlides = array_values(array_filter($payload, 'is_array'));
    }

    public function flushCache(): void
    {
        $this->runtimeSlides = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildPayload(): array
    {
        return HomepageSlide::query()
            ->storefrontVisible()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id', 'eyebrow', 'title', 'description', 'image_path', 'image_url',
                'image_alt', 'image_focal_position', 'show_content', 'show_eyebrow',
                'show_title', 'show_description', 'show_primary_button', 'primary_label',
                'primary_url', 'primary_target', 'show_secondary_button', 'secondary_label',
                'secondary_url', 'secondary_target', 'content_position', 'text_alignment',
                'text_theme', 'overlay_color', 'overlay_opacity',
            ])
            ->map(function (HomepageSlide $slide): ?array {
                $image = $this->resolveImage($slide->image_path, $slide->image_url);
                if ($image === null) {
                    return null;
                }

                return [
                    'id' => (int) $slide->id,
                    'eyebrow' => (string) ($slide->eyebrow ?? ''),
                    'title' => (string) ($slide->title ?? ''),
                    'description' => (string) ($slide->description ?? ''),
                    'image' => $image,
                    'alt' => (string) ($slide->image_alt ?: $slide->title ?: config('storefront.name').' promotion'),
                    'image_focal_position' => $this->enumValue($slide->image_focal_position, ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'], 'center'),
                    'show_content' => (bool) $slide->show_content,
                    'show_eyebrow' => (bool) $slide->show_eyebrow,
                    'show_title' => (bool) $slide->show_title,
                    'show_description' => (bool) $slide->show_description,
                    'show_primary_button' => (bool) $slide->show_primary_button,
                    'primary_label' => (string) ($slide->primary_label ?? ''),
                    'primary_url' => $this->safeUrl($slide->primary_url),
                    'primary_target' => $slide->primary_target === '_blank' ? '_blank' : '_self',
                    'show_secondary_button' => (bool) $slide->show_secondary_button,
                    'secondary_label' => (string) ($slide->secondary_label ?? ''),
                    'secondary_url' => $this->safeUrl($slide->secondary_url),
                    'secondary_target' => $slide->secondary_target === '_blank' ? '_blank' : '_self',
                    'content_position' => $this->enumValue($slide->content_position, ['left', 'center', 'right'], 'left'),
                    'text_alignment' => $this->enumValue($slide->text_alignment, ['left', 'center', 'right'], 'left'),
                    'text_theme' => $slide->text_theme === 'dark' ? 'dark' : 'light',
                    'overlay_rgba' => $this->rgba((string) $slide->overlay_color, (int) $slide->overlay_opacity),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveImage(?string $path, ?string $url): ?string
    {
        if (filled($path)) {
            return Storage::disk('public')->url($path);
        }

        return PublicUrl::isAllowed($url) ? trim((string) $url) : null;
    }

    private function safeUrl(?string $url): string
    {
        return PublicUrl::isAllowed($url) ? trim((string) $url) : '#';
    }

    private function enumValue(?string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? (string) $value : $fallback;
    }

    private function rgba(string $hex, int $opacity): string
    {
        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $hex, $matches) !== 1) {
            $matches[1] = '0D2545';
        }

        $value = $matches[1];
        $alpha = max(0, min(100, $opacity)) / 100;

        return sprintf(
            'rgba(%d,%d,%d,%.2F)',
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
            $alpha
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function legacySlides(): array
    {
        $base = [
            'id' => 0,
            'image_focal_position' => 'center',
            'show_content' => true,
            'show_eyebrow' => true,
            'show_title' => true,
            'show_description' => true,
            'show_primary_button' => true,
            'primary_target' => '_self',
            'show_secondary_button' => true,
            'secondary_target' => '_self',
            'content_position' => 'left',
            'text_alignment' => 'left',
            'text_theme' => 'light',
            'overlay_rgba' => 'rgba(13,37,69,0.72)',
        ];

        return [
            array_merge($base, [
                'eyebrow' => 'Custom jerseys USA',
                'title' => 'Build Your Team Jersey with Name, Number, and Logo',
                'description' => 'Choose colors, add player details, upload your artwork, and prepare your order for teams, schools, clubs, and fans.',
                'image' => 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Custom jerseys and sportswear for team order',
                'primary_label' => 'Shop Custom Jerseys',
                'primary_url' => '#products',
                'secondary_label' => 'Request Team Quote',
                'secondary_url' => '#bulk',
            ]),
            array_merge($base, [
                'eyebrow' => 'Team uniforms',
                'title' => 'Uniform Sets for Schools, Leagues, and Clubs',
                'description' => 'Order full team sets with size lists, player names, numbers, colors, and design support before production.',
                'image' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Custom team uniforms hanging in locker room',
                'primary_label' => 'Shop Uniforms',
                'primary_url' => '#categories',
                'secondary_label' => 'How It Works',
                'secondary_url' => '#process',
            ]),
            array_merge($base, [
                'eyebrow' => 'Bulk orders',
                'title' => 'Bulk Sportswear for Events, Businesses, and Teams',
                'description' => 'Get pricing support for larger quantities, promotional products, caps, bags, hoodies, jerseys, and event apparel.',
                'image' => 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Players on field for sports team event',
                'primary_label' => 'Request Bulk Quote',
                'primary_url' => '#bulk',
                'secondary_label' => 'View Team Gear',
                'secondary_url' => '#gear',
            ]),
        ];
    }

}
