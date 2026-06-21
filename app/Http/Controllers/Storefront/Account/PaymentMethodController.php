<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\StorePaymentMethodRequest;
use App\Models\CustomerPaymentMethod;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly CustomerAccountService $accountService)
    {
    }

    public function index(Request $request): View
    {
        return view('storefront.account.payment-methods', [
            'seo' => $this->seo(),
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
            'wallet' => $this->accountService->paymentWallet($request->user()),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $digits = preg_replace('/\D+/', '', (string) $data['card_number']);
        $makeDefault = $request->boolean('is_default') || $user->customerPaymentMethods()->doesntExist();

        if ($makeDefault) {
            $user->customerPaymentMethods()->update(['is_default' => false]);
        }

        $user->customerPaymentMethods()->create([
            'provider' => 'tokenized-vault-ready',
            'provider_reference' => 'pm_' . Str::lower(Str::random(28)),
            'brand' => $this->cardBrand($digits),
            'last_four' => substr($digits, -4),
            'expiry_month' => (int) $data['expiry_month'],
            'expiry_year' => (int) $data['expiry_year'],
            'nickname' => $this->clean($data['nickname'] ?? null),
            'billing_name' => $this->clean($data['billing_name'] ?? $user->name),
            'is_default' => $makeDefault,
        ]);

        return back()->with('status', 'Payment method saved as a tokenized reference. Raw card number and CVV were not stored.');
    }

    public function makeDefault(Request $request, CustomerPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->user_id === $request->user()->id, 404);

        $request->user()->customerPaymentMethods()->update(['is_default' => false]);
        $paymentMethod->forceFill(['is_default' => true])->save();

        return back()->with('status', 'Default payment method updated.');
    }

    public function destroy(Request $request, CustomerPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->user_id === $request->user()->id, 404);

        $paymentMethod->delete();

        return back()->with('status', 'Payment method removed.');
    }

    /**
     * @return array<string, string>
     */
    private function seo(): array
    {
        return [
            'title' => 'Saved Payment Methods | NextPlay Sportswear',
            'description' => 'Manage saved tokenized payment methods securely for faster checkout.',
            'robots' => 'noindex, nofollow',
        ];
    }

    private function cardBrand(string $digits): string
    {
        return match (true) {
            str_starts_with($digits, '4') => 'Visa',
            preg_match('/^(5[1-5]|2[2-7])/', $digits) === 1 => 'Mastercard',
            preg_match('/^3[47]/', $digits) === 1 => 'American Express',
            preg_match('/^6(?:011|5)/', $digits) === 1 => 'Discover',
            default => 'Card',
        };
    }

    private function clean(?string $value): ?string
    {
        $cleaned = Str::of((string) $value)->stripTags()->trim()->toString();

        return $cleaned === '' ? null : $cleaned;
    }
}
