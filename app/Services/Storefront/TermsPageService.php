<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class TermsPageService
{
    public const PAGE_KEY = 'terms-and-conditions';
    public const CACHE_KEY = 'storefront.page.terms-and-conditions';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'Terms and Conditions',
                'subtitle' => '',
                'description' => 'These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.',
            ],
            'sections' => [
                '01' => [
                    'title' => '1. About these terms',
                    'content' => 'These terms apply when you browse our website, request a quotation, or place an order. Please read them before ordering.',
                ],
                '02' => [
                    'title' => '2. Product information and pricing',
                    'content' => 'Product details, prices, currencies, and available options are shown on the website or in your quotation. Check your selected specifications and order total before payment.',
                ],
                '03' => [
                    'title' => '3. Orders and payment',
                    'content' => 'Review your contact details, addresses, quantities, and customisation instructions before placing an order. Payment details and the amount due are shown at checkout or in your quotation.',
                ],
                '04' => [
                    'title' => '4. Artwork and customisation',
                    'content' => 'We will send artwork for your approval before production begins. Check all names, numbers, logos, colours, sizes, and placement carefully. Production starts after artwork approval.',
                ],
                '05' => [
                    'title' => '5. Production and delivery',
                    'content' => 'Production and delivery estimates are provided with your order. Timing depends on artwork approval, product requirements, and the selected shipping method. Any remote-area charge will be shown before you confirm payment.',
                ],
                '06' => [
                    'title' => '6. Changes and cancellations',
                    'content' => 'Contact us promptly if you need to change or cancel an order. Available options depend on the progress of artwork preparation and production. Your statutory rights are unaffected.',
                ],
                '07' => [
                    'title' => '7. Returns and refunds',
                    'content' => 'Please read our Returns and Refunds Policy for details about custom-made items, samples, and faulty or incorrect products. Nothing in these terms limits your statutory rights.',
                ],
                '08' => [
                    'title' => '8. Uploaded content',
                    'content' => 'Only upload artwork, logos, and other content that you own or have permission to use. You authorise us to use that content to fulfil your order.',
                ],
                '09' => [
                    'title' => '9. Your account',
                    'content' => 'Keep your account details accurate and your password private. Contact us if you suspect unauthorised access.',
                ],
                '10' => [
                    'title' => '10. Privacy',
                    'content' => 'Our Privacy Policy explains how we handle personal information.',
                ],
                '11' => [
                    'title' => '11. Contact us',
                    'content' => 'For questions about these terms or an order, please visit our Contact Us page.',
                ],
            ],
            'seo' => [
                'meta_title' => 'Terms and Conditions | NextPlay Sportswear',
                'meta_description' => 'Terms and conditions governing the purchase of standard and custom sportswear, orders, production and returns.',
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
            'sections' => $payload['sections'] ?? [],
        ];

        $seo = [
            'meta_title' => $payload['seo']['meta_title'] ?? null,
            'meta_description' => $payload['seo']['meta_description'] ?? null,
        ];

        $record = StorefrontPageSetting::updateOrCreate(
            ['page_key' => self::PAGE_KEY],
            [
                'title' => $content['hero']['title'] ?? 'Terms and Conditions',
                'content' => $content,
                'seo' => $seo,
                'updated_by' => $adminId,
            ]
        );

        Cache::forget(self::CACHE_KEY);

        return $record;
    }
}

