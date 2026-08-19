<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductCustomizationChargeComponentTest extends TestCase
{
    public function test_reusable_customization_charge_component_contains_amount_and_basis_fields(): void
    {
        $html = Blade::render('<x-admin.customization-extra-charge />');

        $this->assertStringContainsString('data-customization-extra-charge', $html);
        $this->assertStringContainsString('[price_adjustment]', $html);
        $this->assertStringContainsString('[charge_type]', $html);
        $this->assertStringContainsString('value="included"', $html);
        $this->assertStringContainsString('value="per_unit"', $html);
        $this->assertStringContainsString('value="fixed_order"', $html);
        $this->assertStringContainsString('min="0"', $html);
    }

    public function test_add_product_uses_generic_customization_partial_and_shared_charge_component(): void
    {
        $form = file_get_contents(resource_path('views/admin/products/_form.blade.php'));
        $item = file_get_contents(resource_path('views/admin/products/partials/_selected-customization-option-item.blade.php'));
        $legacyAlias = file_get_contents(resource_path('views/admin/products/partials/_selected-jersey-option-item.blade.php'));

        $this->assertStringContainsString("admin.products.partials._selected-customization-option-item", $form);
        $this->assertStringContainsString('<x-admin.customization-extra-charge />', $item);
        $this->assertStringContainsString("admin.products.partials._selected-customization-option-item", $legacyAlias);
    }
}
