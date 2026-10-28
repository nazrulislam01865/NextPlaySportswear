<?php

require dirname(__DIR__, 2).'/app/Support/PriceTableShipping.php';
require dirname(__DIR__, 2).'/app/Support/OrderShippingCharges.php';

use App\Support\OrderShippingCharges;

$table = [
    'headers' => ['Quantity', 'Product Price (USD)', 'Normal Shipping (USD)', 'Urgent Shipping (USD)', 'Remote Area Surcharge (USD)'],
    'rows' => [['1-4', '4.11', '35.56', '38.92', '35.00'], ['5-11', '3.81', '9.18', '12.63', '7.00'], ['12-23', '3.81', '5.54', '5.50', '2.92']],
];
$line = static fn (string $key, int $quantity, float $extra = 0): array => [
    'key' => $key, 'quantity' => $quantity,
    'line_subtotal' => 10 * $quantity, 'customization_total' => 0,
    'shipping_pricing' => [
        'table' => $table,
        'method' => ['id' => 'normal', 'label' => 'Normal Shipping', 'price_source' => 'price_table', 'extra_charge_per_piece' => $extra],
    ],
    'customization' => ['fulfillment' => ['shipping' => ['label' => 'Normal Shipping', 'amount' => 0]]],
];
$assert = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$a = OrderShippingCharges::forCart([$line('a', 5)]);
$assert($a['shipping'] === 9.18 && $a['remote_rate'] === 7.0, 'Single-product tier mismatch');
$b = OrderShippingCharges::forCart([$line('a', 3), $line('b', 3)]);
$assert($b['shipping'] === 9.18 && $b['remote_rate'] === 7.0, 'Multi-product fee must apply once');
$assert(abs(array_sum(array_column($b['items'], 'line_total')) - 69.18) < 0.001, 'Line totals must reconcile');
$c = OrderShippingCharges::forCart([$line('a', 5, 2)]);
$assert($c['shipping'] === 19.18, 'Per-piece extra must be separate from one shipment rate');
$d = OrderShippingCharges::forCart([$line('a', 2)]);
$assert($d['shipping'] === 35.56 && $d['remote_rate'] === 35.0, 'Small tier mismatch');
$bad = $line('x', 5);
$bad['shipping_pricing']['table']['headers'] = ['Quantity', 'Remote Area Surcharge (USD)'];
$bad['shipping_pricing']['table']['rows'] = [['1-4', '35.00'], ['5-11', '7.00']];
$invalid = OrderShippingCharges::forCart([$bad]);
$assert($invalid['unpriced_shipping'] && ! empty($invalid['items'][0]['configuration_error']), 'Missing table method must block checkout');
echo "Order shipping and remote surcharge regression checks passed.\n";
