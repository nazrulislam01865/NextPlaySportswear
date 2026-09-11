<?php

namespace Tests\Unit;

use App\Services\Shipping\RemoteAreaSurchargeNormalizer;
use PHPUnit\Framework\TestCase;

class RemoteAreaSurchargeNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_numeric_ups_range_and_keeps_legacy_amount_in_sync(): void
    {
        $payload = (new RemoteAreaSurchargeNormalizer())->toModelPayload([
            'carrier' => 'ups',
            'country' => 'United States',
            'iata_code' => 'us',
            'postal_code_low' => '99500',
            'postal_code_high' => '99999',
            'city' => null,
            'origin_surcharge' => 'No',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 18.75,
        ]);

        $this->assertSame('UPS', $payload['carrier']);
        $this->assertSame('US', $payload['iata_code']);
        $this->assertSame(99500, $payload['postal_code_low_numeric']);
        $this->assertSame(99999, $payload['postal_code_high_numeric']);
        $this->assertSame(18.75, $payload['extra_charge']);
        $this->assertSame(18.75, $payload['amount']);
        $this->assertNull($payload['source_hash']);
    }

    public function test_it_treats_the_workbook_city_zero_as_blank_and_preserves_alphanumeric_postcodes(): void
    {
        $normalizer = new RemoteAreaSurchargeNormalizer();
        $payload = $normalizer->toModelPayload([
            'country' => 'United Kingdom',
            'iata_code' => 'GB',
            'postal_code_low' => 'IV1 3',
            'postal_code_high' => 'IV1 3',
            'city' => '0',
            'origin_surcharge' => 'Remote Area Surcharge',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 10,
        ]);

        $this->assertSame('IV13', $payload['postal_code_low_normalized']);
        $this->assertNull($payload['postal_code_low_numeric']);
        $this->assertNull($payload['city']);
        $this->assertNull($payload['city_normalized']);
    }

    public function test_import_rows_can_generate_a_stable_source_hash_without_assigning_one_to_manual_rows(): void
    {
        $normalizer = new RemoteAreaSurchargeNormalizer();
        $row = [
            'country' => 'Canada',
            'iata_code' => 'CA',
            'postal_code_low' => 'A0A1A0',
            'postal_code_high' => 'A0J1V0',
            'city' => null,
            'origin_surcharge' => 'No',
            'destination_surcharge' => 'Extended Area Surcharge',
            'extra_charge' => 12.50,
        ];

        $manual = $normalizer->toModelPayload($row);
        $imported = $normalizer->toModelPayload($row, ['generate_source_hash' => true]);
        $importedAgain = $normalizer->toModelPayload($row, ['generate_source_hash' => true]);

        $this->assertNull($manual['source_hash']);
        $this->assertSame(64, strlen($imported['source_hash']));
        $this->assertSame($imported['source_hash'], $importedAgain['source_hash']);
    }
}
