<?php

require_once dirname(__DIR__, 2).'/app/Support/PriceTableUnitPrice.php';
require_once dirname(__DIR__, 2).'/app/Support/DerivedFabricPriceTable.php';

use App\Support\DerivedFabricPriceTable;
use App\Support\PriceTableUnitPrice;

$table = [
    'headers' => ['Quantity', 'Fabric', 'Product Price', 'Shipping'],
    'highlight_column' => 2,
    'rows' => [
        ['1-9', '130G Butterfly Mesh', '$5.06', '$2'],
        ['1-9', '160G Butterfly Mesh', '$5.05', '$2'],
        ['10+', '130G Butterfly Mesh', '$4.06', '$1'],
        ['10+', '160G Butterfly Mesh', '$4.05', '$1'],
    ],
];
$selected = DerivedFabricPriceTable::forValue($table, ['id' => '130g-butterfly-mesh', 'label' => '130G Butterfly Mesh']);
$unit = PriceTableUnitPrice::forQuantity($selected ?? [], 10);
$first = PriceTableUnitPrice::forQuantity($selected ?? [], 5);
if ($unit !== 4.06 || $first !== 5.06 || round($unit * .8, 2) !== 3.25 || round($first * .8, 2) !== 4.05) {
    fwrite(STDERR, "Quantity/fabric pricing smoke test failed.\n");
    exit(1);
}
echo "Quantity/fabric pricing smoke test passed.\n";
