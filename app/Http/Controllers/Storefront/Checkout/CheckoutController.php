<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Checkout\BillingAddressRequest;
use App\Http\Requests\Storefront\Checkout\CheckoutInformationRequest;
use App\Http\Requests\Storefront\Checkout\PaymentMethodRequest;
use App\Http\Requests\Storefront\Checkout\PlaceOrderRequest;
use App\Http\Requests\Storefront\Checkout\ReviewConfirmationRequest;
use App\Http\Requests\Storefront\Checkout\ShippingAddressRequest;
use App\Http\Requests\Storefront\Checkout\ShippingMethodRequest;
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

        return redirect()->route('checkout.shipping-method')->with('status', 'Billing preference saved.');
    }

    public function shippingMethod(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('shipping_method')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.shipping-method', 'Shipping Method', 'Select a shipping method based on order timeline, production needs, and delivery urgency.', $request, 'shipping_method');
    }

    public function storeShippingMethod(ShippingMethodRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('shipping_method')) {
            return $redirect;
        }

        $this->checkout->storeShippingMethod($request->validated());

        return redirect()->route('checkout.payment-method')->with('status', 'Shipping method selected.');
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

        return $this->view('storefront.checkout.review', 'Order Review', 'Review contact, shipping, billing, payment, customization, and total before placing the order.', $request, 'review');
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

        return redirect()->route('checkout.place-order')->with('status', 'Order details confirmed.');
    }

    public function placeOrder(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('place')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.place-order', 'Place Order', 'Final secure order placement action with confirmation, protection, and processing state.', $request, 'place');
    }

    public function submitOrder(PlaceOrderRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('place')) {
            return $redirect;
        }

        $order = $this->checkout->placeOrder($request->validated(), $request->user());

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
