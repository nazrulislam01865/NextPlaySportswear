<?php

namespace App\Support;

/**
 * Resolve the unit price from the same highlighted price-table cell customers see.
 * Shipping and total columns must never be used as a product unit price.
 */
final class PriceTableUnitPrice
{
    /** @param array<string, mixed> $table */
    public static function forQuantity(array $table, int $quantity): ?float
    {
        $rows = array_values(array_filter((array) ($table['rows'] ?? []), 'is_array'));
        if ($rows === []) {
            return null;
        }

        $tiers = array_values((array) ($table['price_tiers'] ?? []));
        $quantity = max(1, $quantity);
        $selectedIndex = null;
        $fallbackIndex = null;
        $firstIndex = null;

        foreach ($rows as $index => $row) {
            $range = count($tiers) === count($rows) && is_array($tiers[$index])
                ? self::rangeFromTier($tiers[$index])
                : null;
            $range ??= self::rangeFromLabel((string) ($row[0] ?? ''));
            if ($range === null) {
                continue;
            }

            $firstIndex ??= $index;
            if ($quantity >= $range['min']) {
                $fallbackIndex = $index;
                if ($range['max'] === null || $quantity <= $range['max']) {
                    $selectedIndex = $index;
                    break;
                }
            }
        }

        return self::priceInRow($table, $rows[$selectedIndex ?? $fallbackIndex ?? $firstIndex ?? -1] ?? []);
    }

    /** Price at the highest quantity bracket, as used by the storefront 'From' price. */
    public static function startingPrice(array $table): ?float
    {
        $rows = array_values(array_filter((array) ($table['rows'] ?? []), 'is_array'));
        if ($rows === []) {
            return null;
        }

        return self::priceInRow($table, end($rows));
    }

    /** Read the displayed unit price for a specific row (when rows and tiers align). */
    public static function priceInRow(array $table, array $row): ?float
    {
        $column = (int) ($table['highlight_column'] ?? 1);
        if ($column < 1 || ! array_key_exists($column, $row)) {
            return null;
        }

        $value = trim((string) $row[$column]);
        // Do not accidentally interpret percentages, shipping descriptions or
        // other arbitrary text as unit prices.
        if (! preg_match('/^(?:USD\s*|US\$\s*|[$£€]\s*)?(-?\d[\d,]*(?:\.\d+)?)(?:\s*(?:\/|per)\s*(?:pc|pcs|piece|pieces|unit|item))?$/iu', $value, $matches)) {
            return null;
        }

        $price = (float) str_replace(',', '', $matches[1]);

        return is_finite($price) && $price >= 0 ? round($price, 2) : null;
    }

    /** @return array{min: int, max: int|null}|null */
    private static function rangeFromTier(array $tier): ?array
    {
        $minimum = $tier['min'] ?? $tier['minimum_quantity'] ?? null;
        if (! is_numeric($minimum) || (int) $minimum < 1) {
            return null;
        }

        $maximum = $tier['max'] ?? $tier['maximum_quantity'] ?? null;

        return ['min' => (int) $minimum, 'max' => is_numeric($maximum) ? (int) $maximum : null];
    }

    /** @return array{min: int, max: int|null}|null */
    public static function rangeFromLabel(string $label): ?array
    {
        $label = trim(str_ireplace(['–', '—', '−'], '-', str_replace(',', '', $label)));
        $label = trim(preg_replace('/\b(?:pcs?|pieces?|units?|qty|quantity|items?)\b/i', '', $label) ?? '');
        if (preg_match('/^(\d+)\s*(?:-|to)\s*(\d+)$/i', $label, $matches)) {
            return ['min' => max(1, (int) $matches[1]), 'max' => (int) $matches[2]];
        }
        if (preg_match('/^(\d+)\s*(?:\+|plus)?$/i', $label, $matches)) {
            return ['min' => max(1, (int) $matches[1]), 'max' => null];
        }

        return null;
    }
}
