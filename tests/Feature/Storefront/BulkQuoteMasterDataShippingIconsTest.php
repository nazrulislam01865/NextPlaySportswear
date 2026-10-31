<?php

namespace Tests\Feature\Storefront;

use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkQuoteMasterDataShippingIconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_choice_images_are_reused_from_active_master_data_without_changing_form_values(): void
    {
        foreach ([
            'standard' => 'standard-image.webp',
            'expedited' => 'express-image.webp',
            'rush-review' => 'rush-image.webp',
        ] as $code => $file) {
            ShippingMethod::query()->updateOrCreate(
                ['code' => $code],
                ['name' => strtoupper($code), 'image_path' => 'master-data/shipping-methods/'.$file, 'is_active' => true]
            );
        }

        // The recommendation choice should never inherit an unrelated uploaded icon.
        ShippingMethod::query()->updateOrCreate(
            ['code' => 'bulk-freight'],
            ['name' => 'Bulk Freight', 'image_path' => 'master-data/shipping-methods/freight.webp', 'is_active' => true]
        );

        $html = $this->get(route('quote.request'))->assertOk()->getContent();

        foreach ([
            'standard' => 'standard-image.webp',
            'express' => 'express-image.webp',
            'rush' => 'rush-image.webp',
        ] as $value => $filename) {
            $this->assertMatchesRegularExpression(
                '/<option[^>]*value="'.preg_quote($value, '/').'"[^>]*data-icon-url="[^"]*'.preg_quote($filename, '/').'"[^>]*>/s',
                $html
            );
        }
        $this->assertDoesNotMatchRegularExpression('/<option[^>]*value="recommend"[^>]*data-icon-url=/s', $html);
        $this->assertStringContainsString('name="preferred_shipping_method"', $html);
        foreach (['standard', 'express', 'rush', 'recommend'] as $value) {
            $this->assertStringContainsString('value="'.$value.'"', $html);
        }
    }

    public function test_inactive_methods_and_methods_without_images_display_no_uploaded_icon(): void
    {
        ShippingMethod::query()->updateOrCreate(
            ['code' => 'standard'],
            ['name' => 'Standard Shipping', 'image_path' => null, 'image_url' => null, 'is_active' => true]
        );
        ShippingMethod::query()->updateOrCreate(
            ['code' => 'expedited'],
            ['name' => 'Expedited Shipping', 'image_path' => 'master-data/shipping-methods/disabled.webp', 'is_active' => false]
        );
        $html = $this->get(route('quote.request'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<option[^>]*value="standard"[^>]*data-icon-url=/s', $html);
        $this->assertDoesNotMatchRegularExpression('/<option[^>]*value="express"[^>]*data-icon-url=/s', $html);
    }
}
