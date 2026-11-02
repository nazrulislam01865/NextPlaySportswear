<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class PrivacyPageService
{
    public const PAGE_KEY = 'privacy-policy';
    public const CACHE_KEY = 'storefront.page.privacy-policy';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'Privacy Policy',
                'subtitle' => 'Last updated: 22 June 2026',
                'description' => 'This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.',
            ],
            'sections' => [
                '01' => [
                    'title' => '1. Information we collect',
                    'content' => "We may collect your contact details, account information, shipping and billing addresses, order details, customisation instructions, uploaded artwork, support messages, and website usage data.\n\nPayments are processed by our payment provider. Payment records may include transaction references, payment status, and masked card details.",
                ],
                '02' => [
                    'title' => '2. How we use information',
                    'content' => 'We use information to process orders and quotes, prepare artwork, deliver products, provide support, send service updates, prevent fraud, and improve our website.',
                ],
                '03' => [
                    'title' => '3. How information is shared',
                    'content' => 'We may share relevant information with service providers supporting payments, shipping, hosting, email, analytics, fraud prevention, and production. We do not sell personal information.',
                ],
                '04' => [
                    'title' => '4. Retention and security',
                    'content' => 'We retain information for as long as reasonably needed for orders, support, accounting, legal compliance, and security.',
                ],
                '05' => [
                    'title' => '5. Your choices and rights',
                    'content' => 'Depending on your location, you may have rights to access, correct, delete, or export your personal information, or object to certain processing. Contact us to make a request.',
                ],
                '06' => [
                    'title' => '6. Cookies and tracking',
                    'content' => 'We use essential cookies for site functionality and may use performance or analytics cookies to understand how our website is used. See our Cookie Policy for more details.',
                ],
                '07' => [
                    'title' => '7. Third-party links',
                    'content' => 'Our site may link to external websites or services. We are not responsible for their content or privacy practices.',
                ],
                '08' => [
                    'title' => '8. Contact us',
                    'content' => 'If you have questions about this policy or your personal data, please contact our support team.',
                ],
            ],
            'seo' => [
                'meta_title' => 'Privacy Policy | NextPlay Sportswear',
                'meta_description' => 'Learn how NextPlay Sportswear collects, uses, and protects your personal information and data.',
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
                'title' => $content['hero']['title'] ?? 'Privacy Policy',
                'content' => $content,
                'seo' => $seo,
                'updated_by' => $adminId,
            ]
        );

        Cache::forget(self::CACHE_KEY);

        return $record;
    }
}

