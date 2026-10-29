<?php

namespace App\Services\Geography;

use App\Models\CountryCallingCode;
use App\Models\CountryRegion;
use Illuminate\Support\Facades\DB;

/**
 * Non-destructive import: insert missing countries/regions; only fill genuinely blank
 * country text fields and region codes. Existing names, sort orders and status stay as-is.
 */
class ImportMissingGeography
{
    /**
     * @param array<string, array{iso:string, name:string, dial:string, label:string, sort:int|string, active:int|string|bool}> $countries
     * @param array<int, array{iso:string, region:string, code:string, sort:int|string, regionActive:int|string|bool}> $regions
     * @return array{countries:int, regions:int, country_fields:int, region_codes:int}
     */
    public function import(array $countries, array $regions): array
    {
        return DB::transaction(function () use ($countries, $regions): array {
            $now = now();
            $existingCountries = CountryCallingCode::query()
                ->whereIn('iso_code', array_keys($countries))->get()->keyBy('iso_code');
            $insertCountries = [];
            $fillCountries = [];

            foreach ($countries as $iso => $row) {
                $existing = $existingCountries->get($iso);
                if (! $existing) {
                    $insertCountries[] = [
                        'iso_code' => $iso, 'country_name' => $row['name'],
                        'dial_code' => $row['dial'], 'region_label' => $row['label'],
                        'sort_order' => (int) $row['sort'], 'is_active' => (bool) $row['active'],
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                    continue;
                }

                $changes = [];
                foreach (['country_name' => 'name', 'dial_code' => 'dial', 'region_label' => 'label'] as $column => $key) {
                    if (trim((string) $existing->{$column}) === '' && trim((string) $row[$key]) !== '') {
                        $changes[$column] = $row[$key];
                    }
                }
                if ($changes !== []) {
                    // Group by the exact missing columns: never send a nonblank existing field
                    // to the upsert update set, and don't touch sort order or active status.
                    $columns = array_keys($changes);
                    sort($columns);
                    $group = implode('|', $columns);
                    $fillCountries[$group]['columns'] = $columns;
                    $fillCountries[$group]['rows'][] = [
                        'iso_code' => $iso,
                        'country_name' => $changes['country_name'] ?? $existing->country_name,
                        'dial_code' => $changes['dial_code'] ?? $existing->dial_code,
                        'region_label' => $changes['region_label'] ?? $existing->region_label,
                        'sort_order' => $existing->sort_order,
                        'is_active' => $existing->is_active,
                        'created_at' => $existing->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($insertCountries, 200) as $chunk) {
                DB::table('country_calling_codes')->insertOrIgnore($chunk);
            }
            foreach ($fillCountries as $group) {
                foreach (array_chunk($group['rows'], 200) as $chunk) {
                    DB::table('country_calling_codes')->upsert($chunk, ['iso_code'], [...$group['columns'], 'updated_at']);
                }
            }

            if ($regions === []) {
                return [
                    'countries' => count($insertCountries), 'regions' => 0,
                    'country_fields' => array_sum(array_map(fn ($g) => count($g['rows']), $fillCountries)),
                    'region_codes' => 0,
                ];
            }

            $ids = CountryCallingCode::query()->whereIn('iso_code', array_keys($countries))
                ->pluck('id', 'iso_code');
            $names = array_values(array_unique(array_column($regions, 'region')));
            $existingRegions = CountryRegion::query()
                ->whereIn('country_calling_code_id', $ids->values())
                ->whereIn('name', $names)
                ->get()
                ->keyBy(fn (CountryRegion $region) => $region->country_calling_code_id.'|'.mb_strtolower($region->name));
            $insertRegions = [];
            $fillRegionCodes = [];

            foreach ($regions as $row) {
                $countryId = $ids->get($row['iso']);
                if (! $countryId) {
                    continue;
                }
                $key = $countryId.'|'.mb_strtolower($row['region']);
                $existing = $existingRegions->get($key);
                if ($existing) {
                    if (trim((string) $existing->code) === '' && trim($row['code']) !== '') {
                        $fillRegionCodes[] = [
                            'country_calling_code_id' => $countryId, 'name' => $existing->name,
                            'code' => $row['code'], 'sort_order' => $existing->sort_order,
                            'is_active' => $existing->is_active,
                            'created_at' => $existing->created_at ?? $now,
                            'updated_at' => $now,
                        ];
                    }
                    continue;
                }
                $insertRegions[] = [
                    'country_calling_code_id' => $countryId, 'name' => $row['region'],
                    'code' => $row['code'] !== '' ? $row['code'] : null,
                    'sort_order' => (int) $row['sort'],
                    'is_active' => $row['regionActive'] === '' ? true : (bool) $row['regionActive'],
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            foreach (array_chunk($insertRegions, 200) as $chunk) {
                DB::table('country_regions')->insertOrIgnore($chunk);
            }
            foreach (array_chunk($fillRegionCodes, 200) as $chunk) {
                DB::table('country_regions')->upsert($chunk, ['country_calling_code_id', 'name'], ['code', 'updated_at']);
            }

            return [
                'countries' => count($insertCountries), 'regions' => count($insertRegions),
                'country_fields' => array_sum(array_map(fn ($g) => count($g['rows']), $fillCountries)),
                'region_codes' => count($fillRegionCodes),
            ];
        });
    }
}
