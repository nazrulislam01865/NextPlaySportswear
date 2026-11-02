<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class ContactPageService
{
    public const PAGE_KEY = 'contact-us';
    public const CACHE_KEY = 'storefront.page.contact-us';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $rawWa = (string) config('storefront.whatsapp', '+44 7000 000000');
        $waClean = preg_replace('/\D+/', '', $rawWa);
        $waUrl = config('storefront.whatsapp_url') ?: 'https://wa.me/' . ($waClean ?: '447000000000');

        return [
            'hero' => [
                'title' => 'Contact Us',
                'description' => 'Questions about a product or order? Send us a message.',
            ],
            'info' => [
                'heading' => 'Get in touch',
                'address_title' => 'Office address',
                'address' => (string) (config('storefront.address') ?: "NextPlay Sportswear\nUnit 4, Olympic Way\nLondon, SW1A 1AA, United Kingdom"),
                'email_title' => 'Email',
                'email' => (string) (config('storefront.email') ?: 'support@nextplaysportswear.co.uk'),
                'whatsapp_title' => 'WhatsApp',
                'whatsapp_number' => $rawWa ?: '+44 7000 000000',
                'whatsapp_link_text' => 'Chat on WhatsApp',
                'whatsapp_url' => $waUrl,
                'follow_heading' => 'Follow us',
                'instagram_url' => (string) (config('storefront.social.instagram') ?: 'https://instagram.com/nextplaysportswear'),
                'facebook_url' => (string) (config('storefront.social.facebook') ?: 'https://facebook.com/nextplaysportswear'),
                'youtube_url' => (string) (config('storefront.social.youtube') ?: 'https://youtube.com/@nextplaysportswear'),
                'footer_note' => 'For order enquiries, please include your order or quote number.',
            ],
            'form' => [
                'heading' => 'Send us a message',
                'subheading' => 'Fields marked * are required.',
                'submit_btn_text' => 'Send message',
                'privacy_notice' => 'We only use your details to respond to your enquiry. See our Privacy Policy.',
            ],
            'seo' => [
                'meta_title' => 'Contact Us | NextPlay Sportswear',
                'meta_description' => 'Questions about a product or order? Send us a message.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $record = StorefrontPageSetting::where('page_key', self::PAGE_KEY)->first();
            $defaults = $this->defaults();

            if (! $record) {
                return $defaults;
            }

            $content = is_array($record->content) ? $record->content : [];
            $seo = is_array($record->seo) ? $record->seo : [];

            $merged = array_replace_recursive($defaults, $content);
            if (! empty($seo)) {
                $merged['seo'] = array_merge($defaults['seo'], $seo);
            }

            return $merged;
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(array $payload, ?int $adminId = null): StorefrontPageSetting
    {
        $content = [
            'hero' => $payload['hero'] ?? [],
            'info' => $payload['info'] ?? [],
            'form' => $payload['form'] ?? [],
        ];

        $seo = [
            'meta_title' => $payload['seo']['meta_title'] ?? null,
            'meta_description' => $payload['seo']['meta_description'] ?? null,
        ];

        $record = StorefrontPageSetting::updateOrCreate(
            ['page_key' => self::PAGE_KEY],
            [
                'title' => $content['hero']['title'] ?? 'Contact Us',
                'content' => $content,
                'seo' => $seo,
                'updated_by' => $adminId,
            ]
        );

        Cache::forget(self::CACHE_KEY);

        return $record;
    }
}

