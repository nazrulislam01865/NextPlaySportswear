<?php

namespace Tests\Unit;

use App\Support\ProductSizeExtraCharges;
use PHPUnit\Framework\TestCase;

class ProductSizeExtraChargesTest extends TestCase
{
    public function test_indexed_rows_are_normalized_by_size_code(): void
    {
        $adjustments = ProductSizeExtraCharges::adjustments([
            'size_charges' => [
                ['code' => 's', 'label' => 'S', 'amount' => ''],
                ['code' => 'm', 'label' => 'M', 'amount' => '2.50'],
                ['code' => '4xl', 'label' => '4XL', 'amount' => '4'],
            ],
        ]);

        $this->assertSame(0.0, $adjustments['s']);
        $this->assertSame(2.5, $adjustments['m']);
        $this->assertSame(4.0, $adjustments['4xl']);
    }

    public function test_positive_amount_keeps_extra_charges_enabled_as_a_safe_fallback(): void
    {
        $this->assertTrue(ProductSizeExtraCharges::enabled([
            'has_size_extra_charges' => false,
            'size_charges' => [
                ['code' => 'xl', 'label' => 'XL', 'amount' => '3.25'],
            ],
        ]));
    }

    public function test_zero_or_blank_amounts_are_included(): void
    {
        $group = [
            'has_size_extra_charges' => false,
            'size_charges' => [
                ['code' => 's', 'label' => 'S', 'amount' => ''],
                ['code' => 'm', 'label' => 'M', 'amount' => '0'],
            ],
        ];

        $this->assertFalse(ProductSizeExtraCharges::enabled($group));
        $this->assertSame(0.0, ProductSizeExtraCharges::amountFor(ProductSizeExtraCharges::adjustments($group), 'm', 'M'));
    }

    public function test_legacy_associative_payload_is_still_supported(): void
    {
        $adjustments = ProductSizeExtraCharges::adjustments([
            'size_price_adjustments' => ['xl' => '1.75'],
        ]);

        $this->assertSame(1.75, ProductSizeExtraCharges::amountFor($adjustments, 'xl', 'XL'));
    }

    public function test_editor_aliases_keep_saved_amount_visible_when_master_code_changes(): void
    {
        $aliases = ProductSizeExtraCharges::editorAdjustmentAliases('extra-large', 'XL', '2.75');

        $this->assertSame(2.75, $aliases['extra-large']);
        $this->assertSame(2.75, $aliases['XL']);
        $this->assertSame(2.75, $aliases['xl']);
    }
}
