<?php

namespace App\Support;

/**
 * Price delivery once for the complete order. The published price-table shipping
 * and remote columns describe a shipment at a quantity tier, NOT a per-piece fee.
 * Product-specific extra charges still follow their configured per-piece rules.
 * No catalog or remote-area queries are performed by this calculator.
 */
final class OrderShippingCharges
{
    /** @return array{items:array, shipping:float, remote_rate:?float, has_configured_shipping:bool} */
    public static function forCart(array $items): array
    {
        $quantity = array_sum(array_map(static fn (array $item): int => max(0, (int) ($item['quantity'] ?? 0)), $items));
        $baseRate = 0.0;
        $baseIndex = null;
        $remoteRate = null;
        $hasConfiguredShipping = false;
        $extras = [];
        $unpricedShipping = false;

        foreach ($items as $index => $item) {
            $pricing = (array) ($item['shipping_pricing'] ?? []);
            $table = (array) ($pricing['table'] ?? []);
            $headers = (array) ($table['headers'] ?? []);
            $rows = (array) ($table['rows'] ?? []);
            $tiers = (array) ($table['price_tiers'] ?? []);
            if ($quantity > 0 && $headers !== [] && $rows !== []) {
                $remote = PriceTableShipping::remoteSurcharge($headers, $rows, $tiers, $quantity);
                if ($remote !== null) {
                    $remoteRate = max($remoteRate ?? 0.0, $remote);
                }
            }

            $method = (array) ($pricing['method'] ?? []);
            $extras[$index] = 0.0;
            if ($method === []) {
                continue;
            }
            $hasConfiguredShipping = true;
            $lineQuantity = max(0, (int) ($item['quantity'] ?? 0));
            if (($method['price_source'] ?? null) === 'price_table') {
                $rate = $quantity > 0 ? PriceTableShipping::perUnitRate(
                    $headers, $rows, $tiers, $quantity,
                    (string) ($method['label'] ?? ''), (string) ($method['id'] ?? '')
                ) : null;
                if ($rate === null) {
                    $unpricedShipping = true;
                    $items[$index]['configuration_error'] = $items[$index]['configuration_error']
                        ?? 'The selected shipping option has no price for this order quantity. Please update the product or contact us.';
                } elseif ($baseIndex === null || $rate > $baseRate) {
                    $baseRate = max(0, $rate);
                    $baseIndex = $index;
                }
                // Explicitly configured extra-per-piece charge is distinct from
                // the once-per-shipment amount in the price table.
                $extras[$index] = round(max(0, (float) ($method['extra_charge_per_piece'] ?? 0)) * $lineQuantity, 2);
            } elseif (($method['charge_type'] ?? '') === 'fixed_order') {
                $rate = max(0, (float) ($method['price_delta'] ?? 0));
                if ($baseIndex === null || $rate > $baseRate) {
                    $baseRate = $rate;
                    $baseIndex = $index;
                }
            } else {
                // Preserve existing master/per-unit configurations, but do not
                // charge a separate fixed order base for every product line.
                $perItem = max(0, (float) ($method['per_item_price'] ?? 0));
                if (($method['charge_type'] ?? '') === 'master_method') {
                    $rate = max(0, (float) ($method['base_price'] ?? 0) - $perItem);
                    if ($baseIndex === null || $rate > $baseRate) {
                        $baseRate = $rate;
                        $baseIndex = $index;
                    }
                    $extras[$index] = $perItem * $lineQuantity;
                } elseif (($method['charge_type'] ?? '') !== 'included') {
                    $extras[$index] = max(0, (float) ($method['price_delta'] ?? 0)) * $lineQuantity;
                }
            }
        }

        // Allocate the single shipment base to one line so saved order items,
        // totals, and checkout detail breakdowns always reconcile exactly.
        $total = 0.0;
        foreach ($items as $index => &$item) {
            $shipping = round((float) ($extras[$index] ?? 0)
                + ($index === $baseIndex ? $baseRate : 0), 2);
            $total += $shipping;
            $item['product_shipping_total'] = $shipping;
            $item['shipping_unit_price'] = ((int) ($item['quantity'] ?? 0)) > 0
                ? round($shipping / (int) $item['quantity'], 4) : 0.0;
            $item['line_total'] = round((float) ($item['line_subtotal'] ?? 0)
                + (float) ($item['customization_total'] ?? 0) + $shipping, 2);
            if (is_array($item['customization']['fulfillment']['shipping'] ?? null)) {
                $item['customization']['fulfillment']['shipping']['amount'] = $shipping;
                $item['customization']['fulfillment']['shipping']['display_amount'] = '$'.number_format($shipping, 2);
            }
        }
        unset($item);

        return [
            'items' => $items,
            'shipping' => round($total, 2),
            'remote_rate' => $remoteRate,
            'has_configured_shipping' => $hasConfiguredShipping,
            'unpriced_shipping' => $unpricedShipping,
        ];
    }
}
