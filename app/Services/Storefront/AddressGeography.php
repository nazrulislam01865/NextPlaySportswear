<?php

namespace App\Services\Storefront;

use App\Models\CountryCallingCode;
use App\Models\CountryRegion;
use Illuminate\Support\Collection;

/** One country source shared by checkout, addresses, referrals and bulk quotes. */
class AddressGeography
{
    private ?Collection $countries = null;

    public function countries(): Collection
    {
        return $this->countries ??= CountryCallingCode::query()
            ->active()
            ->withExists(['regions as has_regions' => fn ($query) => $query->where('is_active', true)])
            ->ordered()
            ->get(['id', 'iso_code', 'country_name', 'region_label']);
    }

    public function regions(string $iso): Collection
    {
        $country = CountryCallingCode::query()->active()->where('iso_code', strtoupper($iso))->first(['id']);
        if (! $country) {
            return collect();
        }

        return CountryRegion::query()->where('country_calling_code_id', $country->id)
            ->where('is_active', true)->orderBy('sort_order')->orderBy('name')
            ->get(['name', 'code']);
    }
}
