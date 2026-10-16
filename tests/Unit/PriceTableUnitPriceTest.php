<?php

namespace Tests\Unit;

use App\Support\DerivedFabricPriceTable;
use App\Support\PriceTableUnitPrice;
use PHPUnit\Framework\TestCase;

class PriceTableUnitPriceTest extends TestCase
{
    public function test_visible_price_not_stale_tier_is_the_source_for_each_quantity(): void
    {
        $table = [
            'headers' => ['Quantity', 'Product Price', 'Shipping'],
            'highlight_column' => 1,
            'rows' => [['1-9', '$5.06', '$2'], ['10+', '$4.06', '$1']],
            'price_tiers' => [
                ['min' => 1, 'max' => 9, 'unit' => 5.05],
                ['min' => 10, 'max' => null, 'unit' => 4.05],
            ],
        ];

        $this->assertSame(5.06, PriceTableUnitPrice::forQuantity($table, 5));
        $this->assertSame(4.06, PriceTableUnitPrice::forQuantity($table, 10));
        $this->assertSame(4.06, PriceTableUnitPrice::forQuantity($table, 50));
        $this->assertSame(4.06, PriceTableUnitPrice::startingPrice($table));
    }

    public function test_selected_fabric_rows_without_saved_tiers_still_resolve(): void
    {
        $this->assertSame(4.06, PriceTableUnitPrice::forQuantity([
            'rows' => [['1-9', '$5.06'], ['10+', '$4.06']],
            'highlight_column' => 1,
        ], 10));
    }

    public function test_combined_legacy_table_is_resolved_for_selected_fabric_only(): void
    {
        $combined = [
            'headers' => ['Quantity', 'Fabric', 'Product Price', 'Shipping'],
            'highlight_column' => 2,
            'rows' => [
                ['1-9', '130G Butterfly Mesh', '$5.06', '$2'],
                ['1-9', '160G Butterfly Mesh', '$5.05', '$2'],
                ['10+', '130G Butterfly Mesh', '$4.06', '$1'],
                ['10+', '160G Butterfly Mesh', '$4.05', '$1'],
            ],
        ];

        $selected = DerivedFabricPriceTable::forValue($combined, ['id' => '130g-butterfly-mesh', 'label' => '130G Butterfly Mesh']);
        $this->assertNotNull($selected);
        $this->assertCount(2, $selected['rows']);
        $this->assertSame(4.06, PriceTableUnitPrice::forQuantity($selected, 10));
        $this->assertSame(4.06, PriceTableUnitPrice::startingPrice($selected));
    }

    public function test_missing_or_non_price_highlight_cell_falls_back_to_saved_tier(): void
    {
        $this->assertNull(PriceTableUnitPrice::forQuantity([
            'rows' => [['10+', '$4.06', '20%']],
            'highlight_column' => 2,
        ], 10));
    }
}
