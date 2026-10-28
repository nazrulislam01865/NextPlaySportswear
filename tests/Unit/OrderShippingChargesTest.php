<?php

namespace Tests\Unit;

use App\Support\OrderShippingCharges;
use App\Support\PriceTableShipping;
use PHPUnit\Framework\TestCase;

class OrderShippingChargesTest extends TestCase
{
    private function table(): array
    {
        return [
            'headers' => ['Quantity', 'Product Price (USD)', 'Normal Shipping (USD)', 'Urgent Shipping (USD)', 'Remote Area Surcharge (USD)'],
            'rows' => [
                ['1-4', '4.11', '35.56', '38.92', '35.00'],
                ['5-11', '3.81', '9.18', '12.63', '7.00'],
                ['12-23', '3.81', '5.54', '5.50', '2.92'],
                ['24-49', '3.81', '4.19', '4.62', '1.46'],
                ['50-99', '3.81', '3.51', '3.98', '0.70'],
                ['1000', '3.20', '2.88', '3.13', '0.20'],
            ],
            'price_tiers' => [],
        ];
    }

    private function line(string $key, int $quantity, float $extra = 0, string $method = 'normal'): array
    {
        return [
            'key' => $key,
            'quantity' => $quantity,
            'line_subtotal' => 10.0 * $quantity,
            'customization_total' => 0.0,
            'shipping_pricing' => [
                'table' => $this->table(),
                'method' => [
                    'id' => $method,
                    'label' => $method === 'urgent' ? 'Urgent Shipping' : 'Normal Shipping',
                    'price_source' => 'price_table',
                    'extra_charge_per_piece' => $extra,
                ],
            ],
            'customization' => ['fulfillment' => ['shipping' => ['label' => 'Shipping', 'amount' => 0]]],
        ];
    }

    public function test_single_product_uses_one_shipping_and_remote_tier_fee_not_per_piece(): void
    {
        $quote = OrderShippingCharges::forCart([$this->line('a', 5)]);
        $this->assertSame(9.18, $quote['shipping']);
        $this->assertSame(7.0, $quote['remote_rate']);
        $this->assertSame(59.18, $quote['items'][0]['line_total']);
    }

    public function test_multiple_products_use_one_base_at_combined_quantity(): void
    {
        $quote = OrderShippingCharges::forCart([$this->line('a', 3), $this->line('b', 3)]);
        $this->assertSame(9.18, $quote['shipping']);
        $this->assertSame(7.0, $quote['remote_rate']);
        $this->assertSame(9.18, array_sum(array_column($quote['items'], 'product_shipping_total')));
        $this->assertSame(69.18, array_sum(array_column($quote['items'], 'line_total')));
    }

    public function test_admin_per_piece_extra_is_added_without_multiplying_base(): void
    {
        $quote = OrderShippingCharges::forCart([$this->line('a', 5, 2.00)]);
        $this->assertSame(19.18, $quote['shipping']);
        $this->assertSame(7.0, $quote['remote_rate']);
    }

    public function test_remote_column_is_not_replaced_by_a_regular_shipping_column(): void
    {
        $table = $this->table();
        unset($table['headers'][4]);
        $this->assertNull(PriceTableShipping::remoteSurcharge(array_values($table['headers']), $table['rows'], [], 5));
    }

    public function test_missing_shipping_tier_is_not_silently_treated_as_free(): void
    {
        $item = $this->line('a', 5);
        $item['shipping_pricing']['table']['headers'] = ['Quantity', 'Product Price (USD)', 'Remote Area Surcharge (USD)'];
        $item['shipping_pricing']['table']['rows'] = [['1-4', '4.11', '35'], ['5-11', '3.81', '7']];
        $quote = OrderShippingCharges::forCart([$item]);
        $this->assertTrue($quote['unpriced_shipping']);
        $this->assertNotEmpty($quote['items'][0]['configuration_error']);
    }

    public function test_urgent_shipping_uses_urgent_column(): void
    {
        $quote = OrderShippingCharges::forCart([$this->line('a', 5, 0, 'urgent')]);
        $this->assertSame(12.63, $quote['shipping']);
        $this->assertSame(7.0, $quote['remote_rate']);
    }
}
