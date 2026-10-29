<?php

namespace Tests\Feature\Geography;

use App\Models\CountryCallingCode;
use App\Models\CountryRegion;
use App\Services\Storefront\AddressGeography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressGeographyTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_countries_are_the_single_source_for_address_selectors(): void
    {
        $service = app(AddressGeography::class);
        $countries = $service->countries();
        $this->assertTrue($countries->contains('iso_code', 'GB'));
        $this->assertFalse($countries->contains('iso_code', 'ZZ'));

        CountryCallingCode::query()->where('iso_code', 'GB')->update(['is_active' => false]);
        $this->assertFalse((new AddressGeography())->countries()->contains('iso_code', 'GB'));
    }

    public function test_public_region_endpoint_returns_only_active_regions(): void
    {
        $country = CountryCallingCode::query()->where('iso_code', 'CA')->firstOrFail();
        CountryRegion::query()->create(['country_calling_code_id' => $country->id, 'name' => 'Ontario', 'code' => 'ON', 'sort_order' => 1, 'is_active' => true]);
        CountryRegion::query()->create(['country_calling_code_id' => $country->id, 'name' => 'Hidden Province', 'is_active' => false]);

        $this->getJson(route('address.regions', 'CA'))->assertOk()->assertExactJson([
            ['name' => 'Ontario', 'code' => 'ON'],
        ]);
        $this->getJson(route('address.regions', 'BD'))->assertOk()->assertExactJson([]);
        $this->getJson(route('address.regions', 'not-an-iso'))->assertNotFound();
    }
}
