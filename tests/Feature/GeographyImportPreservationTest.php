<?php

namespace Tests\Feature;

use App\Models\CountryCallingCode;
use App\Models\CountryRegion;
use App\Services\Geography\ImportMissingGeography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyImportPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeat_import_keeps_existing_country_region_settings_and_adds_missing_regions(): void
    {
        $country = CountryCallingCode::query()->where('iso_code', 'US')->firstOrFail();
        $country->update([
            'country_name' => 'United States Custom', 'dial_code' => '+999',
            'region_label' => 'Subdivision', 'is_active' => false, 'sort_order' => 999,
        ]);
        $region = CountryRegion::query()->create([
            'country_calling_code_id' => $country->id, 'name' => 'California',
            'code' => 'CUSTOM', 'is_active' => false, 'sort_order' => 700,
        ]);
        $countries = [
            'US' => ['iso' => 'US', 'name' => 'United States', 'dial' => '+1', 'label' => 'State', 'sort' => 10, 'active' => true],
        ];
        $regions = [
            ['iso' => 'US', 'region' => 'California', 'code' => 'CA', 'sort' => 10, 'regionActive' => '1'],
            ['iso' => 'US', 'region' => 'Alaska', 'code' => 'AK', 'sort' => 20, 'regionActive' => ''],
        ];
        $importer = app(ImportMissingGeography::class);
        $first = $importer->import($countries, $regions);
        $second = $importer->import($countries, $regions);

        $country->refresh();
        $region->refresh();
        $this->assertSame('United States Custom', $country->country_name);
        $this->assertSame('+999', $country->dial_code);
        $this->assertSame('Subdivision', $country->region_label);
        $this->assertFalse($country->is_active);
        $this->assertSame(999, $country->sort_order);
        $this->assertSame('CUSTOM', $region->code);
        $this->assertFalse($region->is_active);
        $this->assertSame(700, $region->sort_order);
        $this->assertSame(2, CountryRegion::query()->where('country_calling_code_id', $country->id)->count());
        $this->assertSame(1, $first['regions']);
        $this->assertSame(0, $second['regions']);
        $this->assertSame(0, $second['region_codes']);
    }

    public function test_existing_blank_region_code_is_filled_once_without_changing_anything_else(): void
    {
        $country = CountryCallingCode::query()->where('iso_code', 'GB')->firstOrFail();
        $region = CountryRegion::query()->create([
            'country_calling_code_id' => $country->id, 'name' => 'Barnet',
            'code' => null, 'is_active' => false, 'sort_order' => 543,
        ]);
        $countries = [
            'GB' => ['iso' => 'GB', 'name' => 'United Kingdom', 'dial' => '+44', 'label' => 'County', 'sort' => 30, 'active' => true],
        ];
        $rows = [['iso' => 'GB', 'region' => 'Barnet', 'code' => 'GB-BNE', 'sort' => 20, 'regionActive' => '1']];
        $importer = app(ImportMissingGeography::class);
        $first = $importer->import($countries, $rows);
        $second = $importer->import($countries, $rows);

        $region->refresh();
        $this->assertSame('GB-BNE', $region->code);
        $this->assertFalse($region->is_active);
        $this->assertSame(543, $region->sort_order);
        $this->assertSame(1, $first['region_codes']);
        $this->assertSame(0, $second['region_codes']);
    }
}
