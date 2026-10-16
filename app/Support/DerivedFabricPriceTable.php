<?php

namespace App\Support;

/** Match the storefront's legacy combined-table fabric selection on the server. */
final class DerivedFabricPriceTable
{
    /** @param array<string, mixed> $table @param array<string, mixed> $value */
    public static function forValue(array $table, array $value): ?array
    {
        $headers = array_values((array) ($table['headers'] ?? []));
        $rows = array_values(array_filter((array) ($table['rows'] ?? []), 'is_array'));
        if (count($headers) < 2 || $rows === []) {
            return null;
        }

        $identities = array_values(array_filter(array_map(self::identity(...), [
            $value['id'] ?? null, $value['code'] ?? null, $value['label'] ?? null,
        ]), fn (string $candidate): bool => strlen($candidate) >= 2));
        if ($identities === []) {
            return null;
        }

        $fabricColumn = null;
        $bestMatches = 0;
        foreach ($headers as $index => $header) {
            $matches = 0;
            foreach ($rows as $row) {
                $cell = self::identity($row[$index] ?? null);
                foreach ($identities as $identity) {
                    if ($cell !== '' && ($cell === $identity || str_contains($cell, $identity) || (strlen($identity) >= 5 && str_contains($identity, $cell)))) {
                        $matches++;
                        break;
                    }
                }
            }
            $weightedMatches = $matches + ($matches > 0 && preg_match('/fabric|material|price\s*table/i', (string) $header) ? 0.25 : 0);
            if ($weightedMatches > $bestMatches) {
                $fabricColumn = $index;
                $bestMatches = $weightedMatches;
            }
        }

        if ($fabricColumn === null || $bestMatches === 0) {
            return null;
        }

        $priceColumn = null;
        $quantityColumn = 0;
        foreach ($headers as $index => $header) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', (string) $header) ?? '');
            if (preg_match('/\b(?:qty|quantity)\b/', $normalized)) {
                $quantityColumn = $index;
            }
            if ($index !== $fabricColumn
                && ! preg_match('/\b(?:shipping|shipment|delivery|freight|surcharge)\b/', $normalized)
                && preg_match('/\b(?:product|unit|item) price\b|\bprice (?:product|unit|item)\b/', $normalized)) {
                $priceColumn ??= $index;
            }
        }
        if ($priceColumn === null) {
            $highlight = (int) ($table['highlight_column'] ?? -1);
            $priceColumn = $highlight !== $fabricColumn && isset($headers[$highlight]) && $highlight >= 1
                ? $highlight : null;
        }
        if ($priceColumn === null) {
            return null;
        }

        $selectedRows = array_values(array_filter($rows, function (array $row) use ($fabricColumn, $identities): bool {
            $cell = self::identity($row[$fabricColumn] ?? null);
            foreach ($identities as $identity) {
                if ($cell !== '' && ($cell === $identity || str_contains($cell, $identity) || (strlen($identity) >= 5 && str_contains($identity, $cell)))) {
                    return true;
                }
            }

            return false;
        }));
        if ($selectedRows === []) {
            return null;
        }

        $withoutFabric = static function (array $row) use ($fabricColumn): array {
            unset($row[$fabricColumn]);

            return array_values($row);
        };
        $highlightColumn = $priceColumn - (int) ($priceColumn > $fabricColumn);
        $quantityColumn -= (int) ($quantityColumn > $fabricColumn);
        $derived = [
            'headers' => $withoutFabric($headers),
            'rows' => array_map($withoutFabric, $selectedRows),
            'highlight_column' => $highlightColumn,
            'note' => $table['note'] ?? null,
            'price_tiers' => [],
        ];
        foreach ($derived['rows'] as $row) {
            $range = PriceTableUnitPrice::rangeFromLabel((string) ($row[$quantityColumn] ?? ''));
            $price = PriceTableUnitPrice::priceInRow($derived, $row);
            if ($range !== null && $price !== null) {
                $derived['price_tiers'][] = [
                    'label' => (string) $row[$quantityColumn],
                    'min' => $range['min'],
                    'max' => $range['max'],
                    'unit' => $price,
                    'compare_at' => null,
                ];
            }
        }

        return $derived['price_tiers'] !== [] ? $derived : null;
    }

    private static function identity(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/^(?:master:|code:)/', '', $value) ?? $value;
        $value = preg_replace('/\b(\d+(?:\.\d+)?)\s*g(?:sm)?\b/', '$1gsm', $value) ?? $value;
        $value = preg_replace('/\b(\d+(?:\.\d+)?)\s*(?:grams?|grammes?)\s*(?:per\s*(?:square\s*)?(?:meter|metre|m2))?\b/', '$1gsm', $value) ?? $value;
        $value = preg_replace('/\b(?:fabric|material|price|table|code)\b/', ' ', $value) ?? $value;

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
