<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Payments\PaymentGatewayManager;
use App\Services\Storefront\FooterPaymentMethodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly FooterPaymentMethodService $footerPaymentMethods,
    ) {
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
                'show_in_footer' => false,
                'sort_order' => 0,
            ]),
            'providers' => $this->providersFor(),
        ]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        $data = $this->paymentMethodData($request);
        $storedPath = null;

        if ($request->hasFile('footer_icon')) {
            $storedPath = $request->file('footer_icon')->store('payment-methods/footer-icons', 'public');
            $data['footer_icon_path'] = $storedPath;
        }

        try {
            $method = DB::transaction(fn (): PaymentMethod => PaymentMethod::create($data));
        } catch (Throwable $exception) {
            $this->deleteStoredIcon($storedPath);
            throw $exception;
        }

        $this->syncDefault($method);
        $this->footerPaymentMethods->flush();

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method created successfully. Provider credentials remain server-side only.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', [
            'method' => $paymentMethod,
            'providers' => $this->providersFor($paymentMethod),
        ]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $data = $this->paymentMethodData($request);
        $oldPath = filled($paymentMethod->footer_icon_path) ? (string) $paymentMethod->footer_icon_path : null;
        $newPath = null;
        $removeIcon = $request->boolean('remove_footer_icon');

        if ($request->hasFile('footer_icon')) {
            $newPath = $request->file('footer_icon')->store('payment-methods/footer-icons', 'public');
            $data['footer_icon_path'] = $newPath;
        } elseif ($removeIcon) {
            $data['footer_icon_path'] = null;
        }

        try {
            DB::transaction(fn () => $paymentMethod->update($data));
        } catch (Throwable $exception) {
            $this->deleteStoredIcon($newPath);
            throw $exception;
        }

        if (($newPath !== null || $removeIcon) && $oldPath !== null && $oldPath !== $newPath) {
            $this->deleteStoredIcon($oldPath);
        }

        $paymentMethod->refresh();
        $this->syncDefault($paymentMethod);
        $this->footerPaymentMethods->flush();

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $iconPath = filled($paymentMethod->footer_icon_path) ? (string) $paymentMethod->footer_icon_path : null;

        $paymentMethod->delete();
        $this->deleteStoredIcon($iconPath);

        if (! PaymentMethod::query()->where('is_default', true)->exists()) {
            PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }

        $this->footerPaymentMethods->flush();

        return redirect()->route('admin.payment-methods.index')
            ->with('status', 'Payment method removed.');
    }

    /** @return array<string, mixed> */
    private function paymentMethodData(PaymentMethodRequest $request): array
    {
        $data = $request->validated();
        unset($data['footer_icon'], $data['remove_footer_icon']);

        return $data;
    }

    /** @return array<int, string> */
    private function providersFor(?PaymentMethod $method = null): array
    {
        $providers = $this->gateways->registeredProviders();
        $currentProvider = trim((string) ($method?->provider ?? ''));

        if ($currentProvider !== '' && ! in_array($currentProvider, $providers, true)) {
            $providers[] = $currentProvider;
        }

        return array_values(array_unique($providers));
    }

    private function syncDefault(PaymentMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }

        PaymentMethod::query()->whereKeyNot($method->id)->update(['is_default' => false]);
    }

    private function deleteStoredIcon(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists((string) $path)) {
            Storage::disk('public')->delete((string) $path);
        }
    }
}
