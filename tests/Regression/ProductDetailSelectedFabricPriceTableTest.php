<?php

$root = dirname(__DIR__, 2);
$storefrontJs = (string) file_get_contents($root.'/resources/js/storefront.js');
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$priceTable = (string) file_get_contents($root.'/resources/views/components/storefront/product/customizer/price-table.blade.php');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($storefrontJs, 'fabricPriceTableForValue(value)'), 'Storefront pricing resolves the price table for the selected fabric value.');
$expect(str_contains($storefrontJs, 'selectedFabricPriceValue()'), 'Storefront pricing tracks the currently selected fabric price value.');
$expect(str_contains($storefrontJs, 'return this.selectedFabricPriceValue()?.fabric_price_table || null'), 'Only the selected fabric table becomes the active fabric table.');
$expect(str_contains($storefrontJs, 'derivedFabricPriceTableFromDefault(value)'), 'Legacy combined price tables can be filtered into the selected fabric table at runtime.');
$expect(str_contains($storefrontJs, 'const filteredRows = rows.filter(row => this.fabricPriceCellMatchesValue'), 'Combined tables are filtered to rows belonging to the selected fabric.');
$expect(str_contains($storefrontJs, 'const outputHeaders = headers.filter((_, index) => index !== fabricColumn)'), 'The redundant fabric-name column is removed from a derived selected-fabric table.');
$expect(str_contains($builder, "typeof this.selectedFabricPriceTable === 'function'"), 'Product builder delegates active price-table selection to the reusable selected-fabric resolver.');
$expect(str_contains($builder, 'if (fabricTable && (fabricTable.rows || []).length) return fabricTable'), 'Visible price table prefers the selected fabric rows over the product default table.');
$expect(str_contains($priceTable, 'table().rows || []'), 'Customizer table remains reactive to activePriceTable changes when the fabric selection changes.');

if ($failures !== []) {
    fwrite(STDERR, "Selected fabric price-table regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Selected fabric price-table regression passed.\n";
