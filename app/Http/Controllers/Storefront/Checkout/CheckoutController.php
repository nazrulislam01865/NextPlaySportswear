<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Checkout\BillingAddressRequest;
use App\Http\Requests\Storefront\Checkout\CheckoutInformationRequest;
use App\Http\Requests\Storefront\Checkout\PaymentMethodRequest;
use App\Http\Requests\Storefront\Checkout\PlaceOrderRequest;
use App\Http\Requests\Storefront\Checkout\ReviewConfirmationRequest;
use App\Http\Requests\Storefront\Checkout\ShippingAddressRequest;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    public function information(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        return $this->view('storefront.checkout.information', 'Checkout Information', 'Use saved contact details or add the minimum contact information needed for order updates.', $request, 'information');
    }

    public function storeInformation(CheckoutInformationRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        $this->checkout->storeInformation($request->validated(), $request->user());

        return redirect()->route('checkout.shipping-address')->with('status', 'Contact information saved securely.');
    }

    public function shippingAddress(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('shipping')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.shipping-address', 'Shipping Address Selection', 'Select a saved shipping address or add a new address for your custom sportswear order.', $request, 'shipping');
    }

    public function storeShippingAddress(ShippingAddressRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('shipping')) {
            return $redirect;
        }

        $this->checkout->storeShippingAddress($request->validated(), $request->user());

        return redirect()->route('checkout.billing-address')->with('status', 'Shipping address saved.');
    }

    public function billingAddress(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('billing')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.billing-address', 'Billing Address Selection', 'Choose whether billing address is the same as shipping or add a separate billing address.', $request, 'billing');
    }

    public function storeBillingAddress(BillingAddressRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('billing')) {
            return $redirect;
        }

        $this->checkout->storeBillingAddress($request->validated(), $request->user());

        return redirect()->route('checkout.payment-method')->with('status', 'Billing preference saved. Your product shipping and production selections were carried forward automatically.');
    }

    public function shippingMethod(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('payment')) {
            return $redirect;
        }

        return redirect()
            ->route('checkout.payment-method')
            ->with('status', 'Shipping and production methods were already selected for each product and do not need to be chosen again.');
    }

    public function storeShippingMethod(Request $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('payment')) {
            return $redirect;
        }

        return redirect()
            ->route('checkout.payment-method')
            ->with('status', 'Shipping and production methods were carried from your configured cart items.');
    }

    public function paymentMethod(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('payment')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.payment-method', 'Payment Method', 'Choose a secure payment method for online order payment or bulk quote invoice handling.', $request, 'payment');
    }

    public function storePaymentMethod(PaymentMethodRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('payment')) {
            return $redirect;
        }

        $this->checkout->storePaymentMethod($request->validated(), $request->user());

        return redirect()->route('checkout.review')->with('status', 'Payment method selected. No raw card information was stored.');
    }

    public function review(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('review')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.review', 'Review & Place Order', 'Review contact, shipping, billing, payment, customization, and the final total, then place the order once.', $request, 'review');
    }

    public function storeReview(ReviewConfirmationRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('review')) {
            return $redirect;
        }

        $this->checkout->confirmReview($request->validated());

        return redirect()->route('checkout.review')->with('status', 'Order details confirmed. You can now place the order on this page.');
    }

    public function placeOrder(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        return redirect()->route('checkout.review');
    }

    public function submitOrder(PlaceOrderRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('review')) {
            return $redirect;
        }

        $validated = $request->validated();
        $this->checkout->confirmReview($validated);
        $order = $this->checkout->placeOrder($validated, $request->user());

        return redirect()->route('order.confirmation')->with('status', 'Order snapshot created securely.');
    }

    public function success(Request $request): RedirectResponse
    {
        return redirect()->route('order.confirmation');
    }

    private function view(string $view, string $title, string $description, Request $request, string $currentStep): View
    {
        return view($view, array_merge($this->checkout->pageData($request->user()), [
            'currentStep' => $currentStep,
            'seo' => $this->seo($title, $description),
        ]));
    }

    private function seo(string $title, string $description): array
    {
        return [
            'title' => $title . ' | NextPlay Sportswear',
            'description' => $description,
            'robots' => 'noindex, nofollow',
        ];
    }

    private function guardCart(): ?RedirectResponse
    {
        if (! $this->checkout->hasCheckoutItems()) {
            return redirect()->route('cart.index')->with('status', 'Add at least one product to your cart before checkout.');
        }

        return null;
    }

    private function guardStep(string $currentStep): ?RedirectResponse
    {
        $missingStep = $this->checkout->firstIncompleteStepBefore($currentStep);

        if ($missingStep === null) {
            return null;
        }

        return redirect()
            ->route($missingStep['route'])
            ->with('status', $missingStep['message']);
    }
}
