<?php

namespace App\Support;

class PriceTableShipping
{
    /**
     * Resolve the product/fabric price-table column that belongs to a shipping method.
     *
     * @param array<int, mixed> $headers
     */
    public static function columnIndex(array $headers, string $methodLabel = '', string $methodCode = ''): ?int
    {
        $methodText = self::normalize($methodLabel.' '.$methodCode);
        $category = self::methodCategory($methodText);
        $shippingColumns = [];

        foreach (array_values($headers) as $index => $header) {
            if ($index === 0) {
                continue;
            }

            $normalizedHeader = self::normalize((string) $header);
            if (! self::isShippingColumn($normalizedHeader)) {
                continue;
            }

            $shippingColumns[$index] = $normalizedHeader;
        }

        if ($shippingColumns === []) {
            return null;
        }

        $methodWords = self::significantWords($methodText);
        if ($methodWords !== []) {
            foreach ($shippingColumns as $index => $header) {
                $matchedWords = 0;
                foreach ($methodWords as $word) {
                    if (str_contains(' '.$header.' ', ' '.$word.' ')) {
                        $matchedWords++;
                    }
                }

                if ($matchedWords > 0 && ($matchedWords === count($methodWords) || str_contains($header, ' shipping') || str_contains($header, ' shipment') || str_contains($header, ' delivery'))) {
                    return $index;
                }
            }
        }

        if ($category !== null) {
            foreach ($shippingColumns as $index => $header) {
                if (self::headerMatchesCategory($header, $category)) {
                    return $index;
                }
            }

            // Default/standard methods may use a generic "Shipping" column when only
            // one non-urgent, non-remote shipping column exists.
            if ($category === 'standard') {
                $generic = array_filter($shippingColumns, fn (string $header): bool => ! self::headerMatchesCategory($header, 'urgent') && ! self::headerMatchesCategory($header, 'remote'));
                if (count($generic) === 1) {
                    return array_key_first($generic);
                }
            }
        }

        if (count($shippingColumns) === 1 && $category === 'standard') {
            $index = array_key_first($shippingColumns);
            $header = $shippingColumns[$index] ?? '';

            if (! self::headerMatchesCategory($header, 'urgent') && ! self::headerMatchesCategory($header, 'remote')) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $headers
     * @param array<int, mixed> $rows
     * @param array<int, mixed> $tiers
     */
    public static function perUnitRate(array $headers, array $rows, array $tiers, int $quantity, string $methodLabel = '', string $methodCode = ''): ?float
    {
        $column = self::columnIndex($headers, $methodLabel, $methodCode);
        if ($column === null) {
            return null;
        }

        $row = self::rowForQuantity($rows, $tiers, max(1, $quantity));
        if ($row === null) {
            return null;
        }

        $cells = array_values((array) $row);

        return self::parseMoney($cells[$column] ?? null);
    }

    public static function parseMoney(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:free|included)$/i', $value) === 1) {
            return 0.0;
        }

        if (preg_match('/-?\d[\d,]*(?:\.\d+)?/', $value, $match) !== 1) {
            return null;
        }

        return round((float) str_replace(',', '', $match[0]), 2);
    }

    /**
     * @param array<int, mixed> $rows
     * @param array<int, mixed> $tiers
     * @return array<int, mixed>|null
     */
    private static function rowForQuantity(array $rows, array $tiers, int $quantity): ?array
    {
        $rows = array_values(array_filter($rows, 'is_array'));
        if ($rows === []) {
            return null;
        }

        $ranges = self::rangesForRows($rows, $tiers);

        foreach ($ranges as $index => $range) {
            $minimum = $range['min'];
            $maximum = $range['max'];

            if ($minimum !== null && $quantity >= $minimum && ($maximum === null || $quantity <= $maximum)) {
                return array_values((array) ($rows[$index] ?? []));
            }
        }

        $fallbackIndex = null;
        foreach ($ranges as $index => $range) {
            if (($range['min'] ?? null) !== null && $quantity >= (int) $range['min']) {
                $fallbackIndex = $index;
            }
        }

        if ($fallbackIndex !== null) {
            return array_values((array) ($rows[$fallbackIndex] ?? []));
        }

        return array_values((array) $rows[0]);
    }

    /**
     * @param array<int, mixed> $rows
     * @param array<int, mixed> $tiers
     * @return array<int, array{min:?int,max:?int}>
     */
    private static function rangesForRows(array $rows, array $tiers): array
    {
        $ranges = [];
        $rowCount = count($rows);

        for ($index = 0; $index < $rowCount; $index++) {
            $tier = (array) ($tiers[$index] ?? []);
            $minimum = self::numericInt($tier['min'] ?? $tier['minimum_quantity'] ?? null);
            $maximum = self::numericInt($tier['max'] ?? $tier['maximum_quantity'] ?? null);

            if ($minimum === null) {
                $range = self::parseQuantityRange((array) $rows[$index]);
                $minimum = $range['min'];
                $maximum = $range['max'];
            }

            $ranges[] = ['min' => $minimum, 'max' => $maximum];
        }

        for ($index = 0; $index < $rowCount; $index++) {
            $minimum = $ranges[$index]['min'];
            if ($minimum === null) {
                continue;
            }

            $nextMinimum = null;
            for ($next = $index + 1; $next < $rowCount; $next++) {
                if (($ranges[$next]['min'] ?? null) !== null && (int) $ranges[$next]['min'] > $minimum) {
                    $nextMinimum = (int) $ranges[$next]['min'];
                    break;
                }
            }

            // Imported tables often use breakpoints such as 20, 50, 100 instead of
            // explicit ranges. Treat each breakpoint as valid until the next row.
            if ($nextMinimum !== null && ($ranges[$index]['max'] === null || (int) $ranges[$index]['max'] <= $minimum)) {
                $ranges[$index]['max'] = $nextMinimum - 1;
            }

            if ($nextMinimum === null && $ranges[$index]['max'] !== null && (int) $ranges[$index]['max'] <= $minimum) {
                $ranges[$index]['max'] = null;
            }
        }

        return $ranges;
    }

    /** @param array<int, mixed> $row */
    private static function parseQuantityRange(array $row): array
    {
        $value = html_entity_decode(trim((string) ($row[0] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\xc2\xa0", ',', '–', '—', '−'], [' ', '', '-', '-', '-'], $value);
        $value = preg_replace('/\b(?:pcs?|pieces?|units?|qty|quantity|pairs?|sets?|items?|products?|garments?|shirts?|jerseys?|kits?)\b\.?/i', '', $value) ?? $value;
        $value = trim((string) preg_replace('/\s+/', ' ', $value));
        $value = trim($value, " \t\n\r\0\x0B:-~");

        if ($value === '') {
            return ['min' => null, 'max' => null];
        }

        if (preg_match('/^(\d+)\s*(?:-|to)\s*(\d+)$/i', $value, $match) === 1) {
            return ['min' => (int) $match[1], 'max' => (int) $match[2]];
        }

        if (preg_match('/^(?:at\s+least|min(?:imum)?|>=|=>|≥)?\s*(\d+)\s*(?:\+|plus|and\s+(?:above|up)|or\s+more|or\s+above)?$/i', $value, $match) === 1) {
            return ['min' => (int) $match[1], 'max' => null];
        }

        if (preg_match('/^(?:more\s+than|greater\s+than|over|above|>)\s*(\d+)$/i', $value, $match) === 1) {
            return ['min' => (int) $match[1] + 1, 'max' => null];
        }

        if (preg_match('/^(?:up\s*to|upto|max(?:imum)?|<=|=<|≤)\s*(\d+)$/i', $value, $match) === 1) {
            return ['min' => 1, 'max' => (int) $match[1]];
        }

        return ['min' => null, 'max' => null];
    }

    private static function numericInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value >= 1 ? $value : null;
    }

    private static function isShippingColumn(string $header): bool
    {
        if ($header === '') {
            return false;
        }

        if (preg_match('/\b(total|cost|price|product|unit|piece|jersey|qty|quantity)\b/i', $header) === 1
            && preg_match('/\b(shipping|shipment|delivery|freight|surcharge)\b/i', $header) !== 1) {
            return false;
        }

        if (preg_match('/\b(total\s+est|total\s+cost|cost\s*\/|price\s+per|product\s+price)\b/i', $header) === 1) {
            return false;
        }

        return preg_match('/\b(shipping|shipment|delivery|freight|surcharge)\b/i', $header) === 1;
    }

    private static function methodCategory(string $method): ?string
    {
        if (preg_match('/\b(remote|rural|outlying)\b/i', $method) === 1) {
            return 'remote';
        }

        // Do not treat "rush" as urgent/express automatically. Some products use
        // a manual review-only rush method, and it must show "Contact us for price"
        // unless the price table has a real Rush shipping column.
        if (preg_match('/\b(urgent|express|expedited|emergency|priority|fast|rapid)\b/i', $method) === 1) {
            return 'urgent';
        }

        if (preg_match('/\b(standard|normal|regular|economy|basic|default)\b/i', $method) === 1) {
            return 'standard';
        }

        return null;
    }

    private static function headerMatchesCategory(string $header, string $category): bool
    {
        return match ($category) {
            'remote' => preg_match('/\b(remote|rural|outlying)\b/i', $header) === 1,
            'urgent' => preg_match('/\b(urgent|express|expedited|emergency|priority|fast|rapid)\b/i', $header) === 1,
            'standard' => preg_match('/\b(standard|normal|regular|economy|basic|default)\b/i', $header) === 1,
            default => false,
        };
    }

    /** @return array<int, string> */
    private static function significantWords(string $text): array
    {
        $ignored = ['shipping', 'delivery', 'method', 'service', 'option', 'est', 'estimated', 'the', 'and'];

        return array_values(array_filter(explode(' ', self::normalize($text)), function (string $word) use ($ignored): bool {
            return strlen($word) >= 3 && ! in_array($word, $ignored, true);
        }));
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
