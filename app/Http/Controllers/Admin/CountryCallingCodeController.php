<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CountryCallingCodeRequest;
use App\Models\CountryCallingCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryCallingCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.country-calling-codes.index', [
            'countryCallingCodes' => CountryCallingCode::query()
                ->ordered()
                ->paginate($this->adminPerPage(30))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.country-calling-codes.create', [
            'countryCallingCode' => new CountryCallingCode([
                'is_active' => true,
                'sort_order' => ((int) CountryCallingCode::query()->max('sort_order')) + 10,
            ]),
        ]);
    }

    public function store(CountryCallingCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?: (((int) CountryCallingCode::query()->max('sort_order')) + 10));

        CountryCallingCode::query()->create($data);

        return redirect()->route('admin.country-calling-codes.index')
            ->with('status', 'Country calling code created successfully.');
    }

    public function edit(CountryCallingCode $countryCallingCode): View
    {
        return view('admin.country-calling-codes.edit', compact('countryCallingCode'));
    }

    public function update(CountryCallingCodeRequest $request, CountryCallingCode $countryCallingCode): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? $countryCallingCode->sort_order ?? 0);
        $countryCallingCode->update($data);

        return redirect()->route('admin.country-calling-codes.index')
            ->with('status', 'Country calling code updated successfully.');
    }

    public function destroy(CountryCallingCode $countryCallingCode): RedirectResponse
    {
        $countryCallingCode->delete();

        return redirect()->route('admin.country-calling-codes.index')
            ->with('status', 'Country calling code removed from Master Data. Existing quote phone numbers remain unchanged.');
    }
}
