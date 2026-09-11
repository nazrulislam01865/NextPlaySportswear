<?php

namespace Tests\Feature;

use App\Models\RuralAreaSurcharge;
use App\Services\Shipping\RemoteAreaSurchargeNormalizer;
use App\Services\Shipping\RuralAreaSurchargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoteAreaSurchargeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_postal_range_uses_the_structured_indexed_matcher(): void
    {
        $this->createStructured([
            'country' => 'United States',
            'iata_code' => 'US',
            'postal_code_low' => '99500',
            'postal_code_high' => '99999',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 21.50,
        ]);

        $match = app(RuralAreaSurchargeService::class)->resolve('99601', 'United States');

        $this->assertNotNull($match);
        $this->assertSame(21.50, $match['amount']);
        $this->assertSame('Remote Area Surcharge', $match['destination_surcharge']);
    }

    public function test_city_based_workbook_rows_match_by_city_before_postal_code(): void
    {
        $this->createStructured([
            'country' => 'Nigeria',
            'iata_code' => 'NG',
            'postal_code_low' => '0',
            'postal_code_high' => '0',
            'city' => 'ABAJI',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 19,
        ]);

        $match = app(RuralAreaSurchargeService::class)->resolve('900001', 'Nigeria', null, 'Abaji');

        $this->assertNotNull($match);
        $this->assertSame('ABAJI', $match['city']);
        $this->assertSame(19.0, $match['amount']);
    }

    public function test_uk_prefix_range_matches_a_full_postcode(): void
    {
        $this->createStructured([
            'country' => 'United Kingdom',
            'iata_code' => 'GB',
            'postal_code_low' => 'AB37',
            'postal_code_high' => 'AB38',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 14,
        ]);

        $match = app(RuralAreaSurchargeService::class)->resolve('AB37 9AA', 'United Kingdom');

        $this->assertNotNull($match);
        $this->assertSame(14.0, $match['amount']);
    }

    public function test_destination_no_rows_do_not_apply_a_checkout_charge(): void
    {
        $this->createStructured([
            'country' => 'United States',
            'iata_code' => 'US',
            'postal_code_low' => '10000',
            'postal_code_high' => '19999',
            'destination_surcharge' => 'No',
            'extra_charge' => 99,
        ]);

        $this->assertNull(app(RuralAreaSurchargeService::class)->resolve('12345', 'United States'));
    }

    private function createStructured(array $overrides): RuralAreaSurcharge
    {
        $payload = app(RemoteAreaSurchargeNormalizer::class)->toModelPayload(array_merge([
            'carrier' => 'UPS',
            'country' => 'United States',
            'iata_code' => 'US',
            'postal_code_low' => '00000',
            'postal_code_high' => '00000',
            'city' => null,
            'origin_surcharge' => 'No',
            'destination_surcharge' => 'Extended Area Surcharge',
            'extra_charge' => 10,
            'is_active' => true,
        ], $overrides));

        return RuralAreaSurcharge::query()->create($payload);
    }
}
