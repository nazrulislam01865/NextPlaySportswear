<?php

namespace App\Services\Shipping;

use Illuminate\Support\Str;
use InvalidArgumentException;

class RemoteAreaSurchargeNormalizer
{
    /**
     * Normalize one UPS remote/extended area row into the structured database format.
     * The legacy name/pattern/amount fields are also populated so older code and records
     * remain compatible while the application moves to indexed range-based matching.
     */
    public function toModelPayload(array $row, array $metadata = []): array
    {
        $country = $this->cleanText($row['country'] ?? null, 120);
        $iataCode = Str::upper($this->cleanText($row['iata_code'] ?? null, 3));
        $carrier = Str::upper($this->cleanText($row['carrier'] ?? $metadata['carrier'] ?? 'UPS', 40));
        $low = $this->cleanPostal($row['postal_code_low'] ?? null);
        $high = $this->cleanPostal($row['postal_code_high'] ?? $low);
        $city = $this->cleanCity($row['city'] ?? null);
        $origin = $this->cleanText($row['origin_surcharge'] ?? null, 80);
        $destination = $this->cleanText($row['destination_surcharge'] ?? null, 80);
        $extraCharge = round((float) ($row['extra_charge'] ?? $metadata['extra_charge'] ?? 0), 2);

        if ($country === '' || ! preg_match('/^[A-Z]{2}$/', $iataCode)) {
            throw new InvalidArgumentException('Country and a valid two-letter IATA code are required.');
        }
        if ($low === '' || $high === '') {
            throw new InvalidArgumentException('Both postal-code range values are required.');
        }
        if ($origin === '' || $destination === '') {
            throw new InvalidArgumentException('Origin and destination surcharge values are required.');
        }
        if ($extraCharge < 0 || $extraCharge > 999999.99) {
            throw new InvalidArgumentException('Extra charge must be between 0 and 999999.99.');
        }

        $lowNormalized = $this->normalizePostal($low);
        $highNormalized = $this->normalizePostal($high);
        $lowNumeric = $this->numericPostal($lowNormalized);
        $highNumeric = $this->numericPostal($highNormalized);

        if ($lowNumeric !== null && $highNumeric !== null && $lowNumeric > $highNumeric) {
            throw new InvalidArgumentException('Postal-code low value cannot be greater than the high value.');
        }

        $cityNormalized = $this->normalizeCity($city);
        $rangeLabel = $low === $high ? $low : $low.' - '.$high;
        $locationLabel = $city ?: $rangeLabel;
        $sourceHash = hash('sha256', implode('|', [
            $carrier,
            $iataCode,
            Str::upper($country),
            $lowNormalized,
            $highNormalized,
            $cityNormalized ?? '',
            Str::upper($origin),
            Str::upper($destination),
        ]));

        return [
            'name' => Str::limit($carrier.' '.$iataCode.' '.$locationLabel, 150, ''),
            'carrier' => $carrier,
            'country' => $country,
            'iata_code' => $iataCode,
            'state' => null,
            'postal_code_low' => $low,
            'postal_code_high' => $high,
            'postal_code_low_normalized' => $lowNormalized,
            'postal_code_high_normalized' => $highNormalized,
            'postal_code_low_numeric' => $lowNumeric,
            'postal_code_high_numeric' => $highNumeric,
            'postal_code_patterns' => $rangeLabel,
            'city' => $city,
            'city_normalized' => $cityNormalized,
            'origin_surcharge' => $origin,
            'destination_surcharge' => $destination,
            // Keep amount synchronized for backwards compatibility with existing checkout/order code.
            'amount' => $extraCharge,
            'extra_charge' => $extraCharge,
            'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            'source_file' => array_key_exists('source_file', $metadata) ? $metadata['source_file'] : null,
            'source_row' => array_key_exists('source_row', $metadata) && $metadata['source_row'] !== null
                ? (int) $metadata['source_row']
                : null,
            // Manual records intentionally keep source_hash NULL. Imported rows opt in so
            // repeat imports can upsert safely without making manual overrides disposable.
            'source_hash' => array_key_exists('source_hash', $metadata)
                ? $metadata['source_hash']
                : (($metadata['generate_source_hash'] ?? false) ? $sourceHash : null),
            'import_batch_id' => array_key_exists('import_batch_id', $metadata) ? $metadata['import_batch_id'] : null,
        ];
    }

    public function normalizePostal(?string $value): string
    {
        return Str::upper(preg_replace('/[\s-]+/u', '', trim((string) $value)) ?? '');
    }

    public function normalizeCity(?string $value): ?string
    {
        $city = $this->cleanCity($value);

        return $city === null ? null : Str::upper($city);
    }

    private function cleanPostal(mixed $value): string
    {
        return Str::limit(trim((string) $value), 32, '');
    }

    private function cleanCity(mixed $value): ?string
    {
        $city = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        // The UPS workbook uses numeric zero in the City column as a blank sentinel.
        if ($city === '' || $city === '0') {
            return null;
        }

        return Str::limit($city, 160, '');
    }

    private function cleanText(mixed $value, int $limit): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        return Str::limit($value, $limit, '');
    }

    private function numericPostal(string $value): ?int
    {
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }
}
