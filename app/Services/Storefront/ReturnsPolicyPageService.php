<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class ReturnsPolicyPageService
{
    public const PAGE_KEY = 'returns-policy';
    public const CACHE_KEY = 'storefront.page.returns-policy';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'Returns & Refunds',
                'description' => 'Information about returns, refunds and cancellations.',
            ],
            'sections' => [
                '01' => [
                    'title' => 'Overview',
                    'content' => 'Return options depend on the product, customisation, order stage and condition. Contact us before sending any item back.',
                ],
                '02' => [
                    'title' => 'Standard products',
                    'content' => 'Unused, unworn and unwashed standard products may be eligible for return under the applicable conditions. Keep original tags and packaging.',
                ],
                '03' => [
                    'title' => 'Custom products',
                    'content' => 'Personalised products are made to your approved specifications. Change-of-mind returns may be restricted. This does not affect your rights where goods are faulty or not as described.',
                ],
                '04' => [
                    'title' => 'Damaged or incorrect items',
                    'content' => 'Contact us with your order number, a description of the issue and clear photos of the product, packaging and labels. Keep the packaging until the issue is resolved.',
                ],
                '05' => [
                    'title' => 'Requesting a return',
                    'items' => [
                        'Contact support with your order details.',
                        'Wait for return instructions before shipping.',
                        'Package approved returns securely and retain tracking details.',
                    ],
                ],
                '06' => [
                    'title' => 'Refunds & exchanges',
                    'content' => 'The appropriate resolution may include repair, replacement, exchange or refund, depending on the issue and applicable rights. Approved refunds are normally made to the original payment method.',
                ],
                '07' => [
                    'title' => 'Cancellations',
                    'content' => 'Contact us as soon as possible. Cancellation options depend on whether artwork, materials, or production have been committed. Completed work and committed costs may affect any available refund.',
                ],
                '08' => [
                    'title' => 'Contact us',
                    'content' => 'Need help with an order? Send your order reference and details through our contact form.',
                    'btn_text' => 'Contact Support',
                    'btn_url' => '/contact-us',
                ],
            ],
            'disclaimer' => 'This policy does not affect your statutory rights.',
            'seo' => [
                'meta_title' => 'Returns & Refunds | NextPlay Sportswear',
                'meta_description' => 'Information about returns, refunds and cancellations for standard and custom sportswear orders.',
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
        $setting = StorefrontPageSetting::firstOrNew(['page_key' => self::PAGE_KEY]);

        $seo = $payload['seo'] ?? [];
        unset($payload['seo']);

        $setting->title = $payload['hero']['title'] ?? 'Returns & Refunds';
        $setting->content = $payload;
        $setting->seo = $seo;

        if (! $setting->exists && $adminId) {
            $setting->created_by = $adminId;
        }
        if ($adminId) {
            $setting->updated_by = $adminId;
        }

        $setting->save();

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }
}

