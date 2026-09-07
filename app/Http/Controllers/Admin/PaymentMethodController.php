<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::query()->orderBy('sort_order')->latest()->paginate($this->adminPerPage(20))->withQueryString(),
            'gatewayStatuses' => $this->gateways->statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.create', [
            'method' => new PaymentMethod([
                'provider' => 'manual',
                'payment_type' => 'manual',
                'badge' => 'Manual',
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'providers' => $this->gateways->registeredProviders(),
        ]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        $method = PaymentMethod::create($request->validated());
        $this->syncDefault($method);

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method created successfully. Provider credentials remain server-side only.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', [
            'method' => $paymentMethod,
            'providers' => $this->gateways->registeredProviders(),
        ]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update($request->validated());
        $this->syncDefault($paymentMethod);

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        if (! PaymentMethod::query()->where('is_default', true)->exists()) {
            PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method removed.');
    }

    private function syncDefault(PaymentMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }

        PaymentMethod::query()->whereKeyNot($method->id)->update(['is_default' => false]);
    }
}
