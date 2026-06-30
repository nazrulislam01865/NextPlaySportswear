<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingMethodRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping-methods.index', [
            'methods' => ShippingMethod::query()->orderBy('sort_order')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.shipping-methods.create', [
            'method' => new ShippingMethod([
                'base_price' => 0,
                'per_item_price' => 0,
                'minimum_days' => 1,
                'maximum_days' => 7,
                'starts_after_artwork_approval' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(ShippingMethodRequest $request): RedirectResponse
    {
        $method = ShippingMethod::create($request->validated());
        $this->syncDefault($method);

        return redirect()->route('admin.shipping-methods.index')
            ->with('status', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping-methods.edit', [
            'method' => $shippingMethod,
        ]);
    }

    public function update(ShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->update($request->validated());
        $this->syncDefault($shippingMethod);

        return redirect()->route('admin.shipping-methods.index')
            ->with('status', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->delete();

        if (! ShippingMethod::query()->where('is_default', true)->exists()) {
            ShippingMethod::query()->where('is_active', true)->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }

        return redirect()->route('admin.shipping-methods.index')
            ->with('status', 'Shipping method removed.');
    }

    private function syncDefault(ShippingMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }

        ShippingMethod::query()->whereKeyNot($method->id)->update(['is_default' => false]);
    }
}
