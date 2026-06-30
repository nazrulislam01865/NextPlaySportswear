<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RuralAreaSurchargeRequest;
use App\Models\RuralAreaSurcharge;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RuralAreaSurchargeController extends Controller
{
    public function index(): View
    {
        return view('admin.rural-area-surcharges.index', [
            'surcharges' => RuralAreaSurcharge::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.rural-area-surcharges.create', [
            'surcharge' => new RuralAreaSurcharge([
                'country' => 'United States',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(RuralAreaSurchargeRequest $request): RedirectResponse
    {
        RuralAreaSurcharge::create($request->validated());

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Rural area surcharge created successfully.');
    }

    public function edit(RuralAreaSurcharge $ruralAreaSurcharge): View
    {
        return view('admin.rural-area-surcharges.edit', [
            'surcharge' => $ruralAreaSurcharge,
        ]);
    }

    public function update(RuralAreaSurchargeRequest $request, RuralAreaSurcharge $ruralAreaSurcharge): RedirectResponse
    {
        $ruralAreaSurcharge->update($request->validated());

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Rural area surcharge updated successfully.');
    }

    public function destroy(RuralAreaSurcharge $ruralAreaSurcharge): RedirectResponse
    {
        $ruralAreaSurcharge->delete();

        return redirect()->route('admin.rural-area-surcharges.index')
            ->with('status', 'Rural area surcharge removed.');
    }
}
