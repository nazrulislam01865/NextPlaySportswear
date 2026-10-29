<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryCallingCode;
use App\Models\CountryRegion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CountryRegionController extends Controller
{
    public function index(CountryCallingCode $countryCallingCode): View
    {
        return view('admin.country-calling-codes.regions', [
            'country' => $countryCallingCode,
            'regions' => $countryCallingCode->regions()->orderBy('sort_order')->orderBy('name')->paginate(50),
        ]);
    }

    public function store(Request $request, CountryCallingCode $countryCallingCode): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('country_regions', 'name')->where('country_calling_code_id', $countryCallingCode->id)],
            'code' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
        $countryCallingCode->regions()->create([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('status', 'Region added. It is now available in the storefront dropdown.');
    }

    public function update(Request $request, CountryCallingCode $countryCallingCode, CountryRegion $region): RedirectResponse
    {
        abort_unless($region->country_calling_code_id === $countryCallingCode->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('country_regions', 'name')->where('country_calling_code_id', $countryCallingCode->id)->ignore($region->id)],
            'code' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
        ]);
        $region->update([
            'name' => trim($data['name']),
            'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) $data['is_active'],
        ]);

        return back()->with('status', 'Region updated.');
    }

    public function destroy(CountryCallingCode $countryCallingCode, CountryRegion $region): RedirectResponse
    {
        abort_unless($region->country_calling_code_id === $countryCallingCode->id, 404);
        $region->delete();
        return back()->with('status', 'Region removed. Previously saved order addresses are unchanged.');
    }
}
