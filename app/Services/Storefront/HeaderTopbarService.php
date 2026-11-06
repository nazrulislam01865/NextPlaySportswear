<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

/** Centralized header utility-bar settings. The existing .env values remain the defaults. */
class HeaderTopbarService
{
    public const PAGE_KEY = 'header-topbar';
    public const CACHE_KEY = 'storefront.header-topbar.settings';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'bar_enabled' => true,
            'announcement_enabled' => true,
            'announcement_text' => 'CUSTOM TEAM GEAR • BUILT FOR EVERY GAME',
            'track_order_enabled' => true,
            'track_order_label' => 'Track Order',
            'whatsapp_enabled' => true,
            'whatsapp_label' => 'WhatsApp Us',
            'whatsapp_number' => (string) config('storefront.whatsapp', ''),
            'whatsapp_url' => (string) config('storefront.whatsapp_url', ''),
            'follow_label' => 'Follow us',
            'instagram_enabled' => true,
            'instagram_url' => (string) config('storefront.social.instagram', ''),
            'facebook_enabled' => true,
            'facebook_url' => (string) config('storefront.social.facebook', ''),
            'tiktok_enabled' => true,
            'tiktok_url' => (string) config('storefront.social.tiktok', ''),
            'youtube_enabled' => true,
            'youtube_url' => (string) config('storefront.social.youtube', ''),
            // Empty appearance values preserve the original CSS/theme completely.
            'background_color' => '',
            'text_color' => '',
            'links_gap_px' => null,
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $record = StorefrontPageSetting::query()->where('page_key', self::PAGE_KEY)->first();
            $saved = is_array($record?->content) ? $record->content : [];

            // Only known keys are allowed to reach the storefront view.
            return array_replace($this->defaults(), array_intersect_key($saved, $this->defaults()));
        });
    }

    /** @param array<string, mixed> $validated */
    public function update(array $validated, ?int $adminId): StorefrontPageSetting
    {
        $defaults = $this->defaults();
        $settings = array_replace($defaults, array_intersect_key($validated, $defaults));

        // An unchecked checkbox is false, never a previously saved value.
        foreach ($settings as $key => $value) {
            if (str_ends_with($key, '_enabled')) {
                $settings[$key] = (bool) ($validated[$key] ?? false);
            }
        }

        foreach (['whatsapp_url', 'instagram_url', 'facebook_url', 'tiktok_url', 'youtube_url', 'background_color', 'text_color'] as $key) {
            $settings[$key] = trim((string) ($settings[$key] ?? ''));
        }

        $settings['links_gap_px'] = filled($settings['links_gap_px']) ? (int) $settings['links_gap_px'] : null;

        $record = StorefrontPageSetting::query()->updateOrCreate(
            ['page_key' => self::PAGE_KEY],
            [
                'title' => 'Header & Top Bar',
                'content' => $settings,
                'updated_by' => $adminId,
            ]
        );

        Cache::forget(self::CACHE_KEY);

        return $record;
    }

    /** @return array<string, string> */
    public function activeSocialLinks(array $settings): array
    {
        $links = [];
        foreach (['instagram', 'facebook', 'tiktok', 'youtube'] as $network) {
            $url = trim((string) ($settings[$network.'_url'] ?? ''));
            if (! empty($settings[$network.'_enabled']) && $url !== '' && $url !== '#') {
                $links[$network] = $url;
            }
        }
        return $links;
    }

    public function whatsappUrl(array $settings): string
    {
        $explicit = trim((string) ($settings['whatsapp_url'] ?? ''));
        if ($explicit !== '' && $explicit !== '#') {
            return $explicit;
        }

        $digits = preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? ''));
        return $digits !== '' ? 'https://wa.me/'.$digits : '#';
    }
}
