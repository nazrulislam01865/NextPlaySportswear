<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteRequest;

/** Quote conversion terms; never estimate pricing from a PDF or a quantity range. */
final class BulkQuoteOrderTerms
{
    public static function pricePending(BulkQuoteRequest $quote): bool
    {
        return (bool) data_get($quote->quotation_details, 'pricing_pending', false)
            && $quote->quoted_amount === null;
    }

    /** @return array<int,array{group_id:string,group_label:string,size_code:string,size_label:string,quantity:int}> */
    public static function sizeBreakdown(BulkQuoteRequest $quote): array
    {
        $lines = [];
        foreach ((array) data_get($quote->product_information, 'size_groups', []) as $group) {
            if (! is_array($group)) continue;
            $label = (string) ($group['label'] ?? 'Sizes');
            foreach ((array) ($group['sizes'] ?? []) as $size) {
                if (! is_array($size) || (int) ($size['quantity'] ?? 0) < 1) continue;
                $code = (string) ($size['size'] ?? $size['label'] ?? 'Size');
                $lines[] = [
                    'group_id' => '', 'group_label' => $label,
                    'size_code' => $code, 'size_label' => $code,
                    'quantity' => (int) $size['quantity'],
                ];
            }
        }
        return $lines;
    }

    public static function exactQuantity(BulkQuoteRequest $quote): int
    {
        $pricedQuantity = (int) data_get($quote->quotation_details, 'quantity', 0);
        if ($pricedQuantity > 0) return $pricedQuantity;

        $info = (array) ($quote->product_information ?? []);
        $sizeTotal = 0;
        foreach ((array) ($info['size_groups'] ?? []) as $group) {
            if (! is_array($group)) continue;
            foreach ((array) ($group['sizes'] ?? []) as $size) {
                if (is_array($size) && filter_var($size['quantity'] ?? null, FILTER_VALIDATE_INT) !== false) {
                    $sizeTotal += max(0, (int) $size['quantity']);
                }
            }
        }
        if ($sizeTotal > 0) return $sizeTotal;

        $selectionTotal = 0;
        foreach ((array) ($info['selected_products'] ?? []) as $item) {
            if (is_array($item) && filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT) !== false) {
                $selectionTotal += max(0, (int) $item['quantity']);
            }
        }
        if ($selectionTotal > 0) return $selectionTotal;

        // A range such as "10-49" is not an agreed order quantity.
        $estimated = trim((string) ($quote->estimated_quantity ?? ''));
        return preg_match('/^[1-9][0-9]*$/D', $estimated) ? (int) $estimated : 0;
    }
}
