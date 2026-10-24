<?php

namespace Tests\Regression;

use App\Support\ProductStorefrontUi;
use Tests\TestCase;

class ProductDetailIconCleanupTest extends TestCase
{
    public function test_size_chart_label_updates_previous_default_without_overriding_custom_text(): void
    {
        $this->assertSame('View Size Chart', ProductStorefrontUi::defaults()['size_guide_label']);
        $this->assertSame('View Size Chart', ProductStorefrontUi::settings(['size_guide_label' => 'View Size Guide'])['size_guide_label']);
        $this->assertSame('See Our Sizing', ProductStorefrontUi::settings(['size_guide_label' => 'See Our Sizing'])['size_guide_label']);
    }

    public function test_unwanted_heading_button_and_summary_icons_are_absent(): void
    {
        $builder = file_get_contents(resource_path('views/components/storefront/product/builder.blade.php'));
        $summary = file_get_contents(resource_path('views/components/storefront/product/customizer/order-summary.blade.php'));
        $this->assertStringNotContainsString('name="truck" /> {{ $ui[\'shipping_method_label\'] }}', $builder);
        $this->assertStringNotContainsString('name="calendar" /> {{ $ui[\'estimated_delivery_title\'] }}', $builder);
        $this->assertStringNotContainsString('<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 4 6 6', $builder);
        $this->assertStringNotContainsString('order_summary_player_icon', $summary);
        $this->assertStringNotContainsString('order_summary_artwork_icon', $summary);
        $this->assertStringNotContainsString('order_summary_shipping_icon', $summary);
        $this->assertSame(0, substr_count($summary, 'class="np-custom-order-icon"'));
    }

    public function test_artwork_and_delivery_icon_upload_controls_remain_available(): void
    {
        $admin = file_get_contents(resource_path('views/components/admin/product-storefront-ui-settings.blade.php'));
        foreach (['artwork_drop_icon', 'artwork_info_icon', 'production_time_icon', 'shipping_time_icon', 'estimated_delivery_icon'] as $key) {
            $this->assertStringContainsString('setting-key="'.$key.'"', $admin);
        }
        foreach (['order_summary_player_icon', 'order_summary_artwork_icon', 'order_summary_shipping_icon', 'shipping_method_icon', 'estimated_delivery_title_icon'] as $key) {
            $this->assertStringNotContainsString('setting-key="'.$key.'"', $admin);
        }
    }

    public function test_delivery_result_uses_same_ash_background_as_other_segments(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));
        $this->assertStringContainsString('.np-product-detail-prototype .np-proto-delivery-equation .is-estimate {', $css);
        $this->assertStringContainsString('background: transparent;', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) auto;', $css);
    }

    public function test_roster_summary_icons_are_uploadable_and_keep_the_artwork_reference_size(): void
    {
        $iconKeys = ProductStorefrontUi::iconKeys();
        $builder = file_get_contents(resource_path('views/components/storefront/product/builder.blade.php'));
        $admin = file_get_contents(resource_path('views/components/admin/product-storefront-ui-settings.blade.php'));
        $css = file_get_contents(resource_path('css/storefront.css'));

        foreach (['roster_total_pieces_icon', 'roster_players_icon'] as $key) {
            $this->assertContains($key, $iconKeys);
            $this->assertNull(ProductStorefrontUi::defaults()[$key]);
            $this->assertStringContainsString('setting-key="'.$key.'"', $admin);
            $this->assertStringContainsString("\$ui['".$key."']", $builder);
        }

        $this->assertStringContainsString('var(--np-product-ui-icon-size, 36px)', $css);
        $this->assertStringContainsString('np-proto-player-stat > .np-configurable-ui-icon', $css);
    }
}
