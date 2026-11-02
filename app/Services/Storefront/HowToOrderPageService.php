<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class HowToOrderPageService
{
    public const PAGE_KEY = 'how-to-order';
    public const CACHE_KEY = 'storefront.page.how-to-order';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'How to Order',
                'description' => 'From finding your product to receiving your custom order.',
            ],
            'sections' => [
                '01' => [
                    'title' => 'Find your product',
                    'description' => 'Search by product name or SKU using the top search bar. Browse sports and product categories, or explore featured, top and related products across the site.',
                    'search_placeholder' => 'Search jerseys, caps, bags...',
                    'browse_text' => 'Browse Products',
                    'browse_url' => '/products',
                ],
                '02' => [
                    'title' => 'Check the product details',
                    'description' => 'Review photos, description, specifications, materials, size charts, minimum quantities and available customisation options before choosing.',
                ],
                '03' => [
                    'title' => 'Customise your item step by step',
                    'p1' => 'Select the available options on the product page. Add sizes and quantities, colours, names, numbers and logo placement where relevant. Upload artwork or reference files and add any instructions.',
                    'p2' => 'Check your selections, then choose Add to Cart. Repeat for each product.',
                    'btn_text' => 'Add to Cart',
                ],
                '04' => [
                    'title' => 'Review your cart',
                    'description' => 'Check each product, size, quantity, customisation and price. Edit or remove items before continuing to checkout.',
                ],
                '05' => [
                    'title' => 'Choose contacts and delivery',
                    'intro' => 'Provide your main order contact, then choose combined or separate shipments.',
                    'ship_together_label' => 'Ship together',
                    'ship_together_desc' => 'One address; dispatch after all products are ready.',
                    'ship_separately_label' => 'Ship separately',
                    'ship_separately_desc' => 'Each product can have its own recipient, address, shipping method and timeline; the same address can also be used.',
                    'p1' => 'One order and one payment. Each product is managed as a separate sub-order.',
                    'p2' => 'Choose Regular (Standard) or Express (Expedited) shipping where available. Review delivery charges and any remote-area surcharge before paying.',
                ],
                '06' => [
                    'title' => 'Review and place your order',
                    'description' => 'Check all contact and delivery details, product selections, production estimates, shipping ranges, remote-area charges and the final total. Enter billing and payment details, then place your order.',
                ],
                '07' => [
                    'title' => 'Review and approve your artwork',
                    'description' => 'After receiving your order, our team prepares an artwork proof. Check names, spelling, numbers, colours, dimensions and placement. Request changes if needed, then approve the final artwork for each product.',
                    'notice' => 'Production begins only after final artwork approval.',
                ],
                '08' => [
                    'title' => 'Production, dispatch and tracking',
                    'p1' => 'Production time depends on the product and quantity. The production clock starts after artwork approval. Shipping time is additional and starts after production is complete and the parcel is dispatched.',
                    'formula' => 'Estimated arrival = artwork approval time + production working days + shipping working days',
                    'p2' => 'Production and shipping estimates use working days. Check the quoted ranges before ordering, especially for an event deadline.',
                    'p3' => 'After dispatch, the relevant sub-order status changes to Shipped and its tracking number is added. Open Track Your Order using your order number and the email used at checkout. Separate shipments have separate tracking details.',
                    'link_1_text' => 'Artwork Guidelines',
                    'link_1_url' => '/artwork-guidelines',
                    'link_2_text' => 'Shipping & Delivery',
                    'link_2_url' => '/shipping-delivery',
                    'btn_track_text' => 'Track Your Order',
                    'btn_track_url' => '/track-order',
                    'link_3_text' => 'Contact Us',
                    'link_3_url' => '/contact-us',
                ],
            ],
            'seo' => [
                'meta_title' => 'How to Order | NextPlay Sportswear',
                'meta_description' => 'From finding your product to receiving your custom order.',
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

        $setting->title = $payload['hero']['title'] ?? 'How to Order';
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

