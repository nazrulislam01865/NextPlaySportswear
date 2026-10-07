<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductDetailUiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProductStorefrontUi
{
    private const CACHE_KEY = 'storefront.product-detail-ui.v1';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'save_enabled' => true,
            'save_label' => 'Save',
            'save_icon' => null,
            'share_enabled' => true,
            'share_label' => 'Share',
            'share_icon' => null,
            'sample_label' => 'Request a Sample',
            'sample_icon' => null,

            'sizes_label' => 'Sizes',
            'sizes_available_label' => 'Sizes Available',
            'sizes_icon' => null,
            'minimum_order_label' => 'Minimum Order Quantity',
            'minimum_order_icon' => null,
            'size_guide_label' => 'View Size Guide',
            'size_guide_icon' => null,
            'multiple_sizes_note' => 'You can add multiple sizes. Final price will be calculated based on total quantity.',
            'multiple_sizes_note_icon' => null,

            'artwork_step_title' => 'Upload Artwork',
            'artwork_step_description' => 'Add your design files, choose from existing designs, or request our design support.',
            'artwork_upload_tab_title' => 'Upload New Artwork',
            'artwork_upload_tab_description' => 'Upload your files for us to review.',
            'artwork_upload_tab_icon' => null,
            'artwork_existing_tab_title' => 'Use Existing Design',
            'artwork_existing_tab_description' => 'Choose from your previously uploaded designs.',
            'artwork_existing_tab_icon' => null,
            'artwork_help_tab_title' => 'Need Help with Artwork?',
            'artwork_help_tab_description' => 'Our design team will assist you.',
            'artwork_help_tab_icon' => null,
            'artwork_drop_title' => 'Drag & drop files here',
            'artwork_browse_text' => 'or click to browse',
            'artwork_drop_icon' => null,
            'artwork_formats_label' => 'Supported file formats',
            'artwork_max_size_label' => 'Max file size',
            'artwork_multiple_files_text' => 'You can upload multiple files.',
            'artwork_next_title' => 'What happens next?',
            'artwork_next_lines' => "We will review your artwork for print readiness.\nIf any adjustments are needed, our team will contact you.\nYou can continue to the next step now, or save and come back later.",
            'artwork_info_icon' => null,
            'artwork_help_next_title' => 'What happens next?',
            'artwork_help_next_lines' => "Our design team will prepare options based on your requirements.\nWe will send the artwork to you for approval through the normal order communication process.\nYou can continue to the next step now.",

            'production_step_title' => 'Production & Shipping',
            'production_step_description' => 'Production time is calculated automatically from your quantity. Choose the shipping method that works for your order.',
            'production_lead_time_label' => 'Production Lead Time',
            'production_lead_time_icon' => null,
            'shipping_method_label' => 'Shipping Method',
            'shipping_method_icon' => null,
            'shipping_method_card_icon' => null,
            'estimated_delivery_title' => 'Estimated Delivery',
            'estimated_delivery_title_icon' => null,
            'production_time_label' => 'Production Time',
            'production_time_icon' => null,
            'shipping_time_label' => 'Shipping Time',
            'shipping_time_icon' => null,
            'estimated_delivery_label' => 'Estimated Delivery',
            'estimated_delivery_icon' => null,
            'estimated_delivery_note' => '(After order confirmation)',
            'worldwide_shipping_title' => 'Worldwide Shipping',
            'worldwide_shipping_text' => 'We ship worldwide including USA. Final shipping cost is calculated from the selected shipping method and order configuration.',
            'worldwide_shipping_icon' => null,
            'important_notes_title' => 'Important Notes',
            'important_notes_lines' => "Production starts after artwork approval and payment confirmation.\nDelivery time may vary based on order quantity, destination and customs clearance.\nYou will receive tracking information once your order ships.",
            'important_notes_icon' => null,
        ];
    }

    /** @return array<int, string> */
    public static function iconKeys(): array
    {
        return [
            'save_icon', 'share_icon', 'sample_icon',
            'sizes_icon', 'minimum_order_icon', 'size_guide_icon', 'multiple_sizes_note_icon',
            'artwork_upload_tab_icon', 'artwork_existing_tab_icon', 'artwork_help_tab_icon',
            'artwork_drop_icon', 'artwork_info_icon',
            'production_lead_time_icon', 'shipping_method_icon', 'shipping_method_card_icon',
            'estimated_delivery_title_icon', 'production_time_icon', 'shipping_time_icon',
            'estimated_delivery_icon', 'worldwide_shipping_icon', 'important_notes_icon',
        ];
    }

    /** @return array<int, string> */
    public static function booleanKeys(): array
    {
        return ['save_enabled', 'share_enabled'];
    }

    /**
     * Return the centralized product-detail UI settings.
     *
     * Product-specific settings are intentionally no longer read here. The legacy
     * products.storefront_ui_settings column remains untouched for compatibility,
     * but the storefront is controlled by this single centralized record.
     *
     * @return array<string, mixed>
     */
    public static function globalSettings(): array
    {
        $stored = [];

        try {
            if (Schema::hasTable('product_detail_ui_settings')) {
                $ttl = max(1, (int) config('storefront.product_detail_ui_cache_seconds', 600));
                $stored = Cache::remember(self::CACHE_KEY, $ttl, function (): array {
                    $record = ProductDetailUiSetting::query()->whereKey(1)->first();
                    return is_array($record?->settings) ? $record->settings : [];
                });
            }
        } catch (\Throwable) {
            $stored = [];
        }

        return self::normalize($stored);
    }

    /** @return array<string, mixed> */
    public static function settings(Product|array|null $source = null): array
    {
        // Storefront Blade components receive the already-normalized array from the
        // catalog service. Preserve that fast path and do not hit the database again.
        if (is_array($source)) {
            return self::normalize($source);
        }

        return self::globalSettings();
    }

    /** @return array<string, mixed> */
    public static function forStorefront(Product $product): array
    {
        $settings = self::globalSettings();

        foreach (self::iconKeys() as $key) {
            $settings[$key] = filled($settings[$key] ?? null)
                ? PublicMedia::url((string) $settings[$key])
                : null;
        }

        return $settings;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, string> */
    public static function lines(mixed $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn ($line): string => trim((string) $line))
            ->filter()
            ->take(6)
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $stored
     *  @return array<string, mixed>
     */
    private static function normalize(array $stored): array
    {
        $settings = array_replace(self::defaults(), array_intersect_key($stored, self::defaults()));

        foreach (self::booleanKeys() as $key) {
            $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $settings;
    }
}
