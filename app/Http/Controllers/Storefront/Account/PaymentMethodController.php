<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerPaymentMethod;
use App\Payments\PaymentGatewayManager;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $accountService,
        private readonly PaymentGatewayManager $gateways,
    ) {
    }

    public function index(Request $request): View
    {
        return view('storefront.account.payment-methods', [
            'seo' => $this->seo(),
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
            'wallet' => $this->accountService->paymentWallet($request->user()),
            'stripeReady' => $this->gateways->isAvailable('stripe'),
        ]);
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

        return back()->with('status', 'Payment method reference removed.');
    }

    private function seo(): array
    {
        return [
            'title' => 'Saved Payment Methods | NextPlay Sportswear',
            'description' => 'Manage provider-tokenized payment references securely.',
            'robots' => 'noindex, nofollow',
        ];
    }
}
