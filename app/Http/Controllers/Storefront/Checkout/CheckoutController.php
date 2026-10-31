<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Http\Requests\Storefront\Checkout\BillingAddressRequest;
use App\Http\Requests\Storefront\Checkout\CheckoutInformationRequest;
use App\Http\Requests\Storefront\Checkout\PaymentMethodRequest;
use App\Http\Requests\Storefront\Checkout\PlaceOrderRequest;
use App\Http\Requests\Storefront\Checkout\ReviewConfirmationRequest;
use App\Http\Requests\Storefront\Checkout\ReferralCheckoutRequest;
use App\Http\Requests\Storefront\Checkout\ShippingAddressRequest;
use App\Services\Checkout\CheckoutService;
use App\Services\Referrals\ReferralOfferService;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PaymentOrchestrator $payments,
        private readonly ReferralOfferService $referrals,
    ) {
    }


    public function referral(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        $offer = $this->referrals->current($request->user());
        if (! is_array($offer)) {
            return redirect()->route('checkout.information');
        }

        return $this->view('storefront.checkout.referral', 'Secure Checkout', 'Complete your delivery and payment preferences with your referral offer linked to this visit.', $request, 'information');
    }

    public function storeReferral(ReferralCheckoutRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if (! is_array($this->referrals->current($request->user()))) {
            return redirect()->route('checkout.information');
        }

        $data = $request->validated();

        $this->checkout->storeInformation([
            'contact_choice' => 'new',
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'order_note' => 'Referral checkout delivery preference: '.($data['delivery_preference'] === 'express' ? 'Express delivery' : 'Standard delivery'),
            'save_to_account' => false,
        ], $request->user());

        $this->checkout->storeShippingAddress([
            'address_choice' => 'new',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'company_name' => '',
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? '',
            'city' => $data['city'],
            'state' => $data['state'] ?? '',
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'],
            'delivery_instruction' => '',
            'save_to_account' => false,
        ], $request->user());

        if ((bool) ($data['billing_same_as_shipping'] ?? false)) {
            $this->checkout->storeBillingAddress(['same_as_shipping' => true], $request->user());
        } else {
            $this->checkout->storeBillingAddress([
                'same_as_shipping' => false,
                'address_choice' => 'new',
                'first_name' => $data['billing_first_name'],
                'last_name' => $data['billing_last_name'],
                'company_name' => '',
                'address_line_1' => $data['billing_address_line_1'],
                'address_line_2' => $data['billing_address_line_2'] ?? '',
                'city' => $data['billing_city'],
                'state' => $data['billing_state'] ?? '',
                'country' => $data['billing_country'],
                'postal_code' => $data['billing_postal_code'],
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'],
                'save_to_account' => false,
            ], $request->user());
        }

        $this->checkout->storePaymentMethod([
            'payment_method' => $data['payment_method'],
        ], $request->user());

        return redirect()->route('checkout.review')->with('status', 'Checkout details saved. Review the final total before placing your order.');
    }

    public function information(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if (is_array($this->referrals->current($request->user()))) {
            return redirect()->route('checkout.referral');
        }

        return $this->view('storefront.checkout.information', 'Checkout Information', 'Use saved contact details or add the minimum contact information needed for order updates.', $request, 'information');
    }

    public function storeInformation(CheckoutInformationRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        $this->checkout->storeInformation($request->validated(), $request->user());

        return redirect()->route('checkout.shipping-address');
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

        return redirect()->route('checkout.billing-address');
    }

    public function shippingPreview(ShippingAddressRequest $request): \Illuminate\Http\JsonResponse
    {
        if (! $this->checkout->hasCheckoutItems() || $this->checkout->firstIncompleteStepBefore('shipping')) {
            return response()->json(['message' => 'Complete your contact information first.'], 422);
        }
        return response()->json($this->checkout->previewShippingCharges($request->validated(), $request->user()));
    }

    public function remoteAreaStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! $this->checkout->hasCheckoutItems()) {
            return response()->json(['remote' => false], 422);
        }

        $data = $request->validate([
            'postal_code' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'country' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
        ]);

        // Reuse the same postcode matcher as order pricing. Never infer from prefixes in JavaScript.
        $matched = app(\App\Services\Shipping\RuralAreaSurchargeService::class)->resolve(
            $data['postal_code'], $data['country'], $data['state'] ?? null, $data['city'] ?? null
        );

        return response()->json(['remote' => $matched !== null]);
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

        return redirect()->route('checkout.review');
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
            ->route('checkout.review')
            ->with('status', 'Production is calculated automatically from each product quantity, and shipping is already configured for each item.');
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
            ->route('checkout.review')
            ->with('status', 'Production was calculated automatically from quantity and shipping was carried from your configured cart items.');
    }

    public function paymentMethod(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        return redirect()->route('checkout.review');
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

        return redirect()->route('checkout.review');
    }

    public function review(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('review')) {
            return $redirect;
        }

        return $this->view('storefront.checkout.review', 'Review Order', 'Review your details before completing secure payment.', $request, 'review');
    }

    public function storeReview(ReviewConfirmationRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardCart()) {
            return $redirect;
        }

        if ($redirect = $this->guardStep('review')) {
            return $redirect;
        }

        try {
            $this->checkout->selectStripePayment($request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }
        $this->checkout->confirmReview($request->validated());

        return redirect()->route('checkout.review');
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
        // Select the available Stripe-hosted method only on final submission.
        // Customer card details never enter this Laravel application.
        // Do not create an unpaid order when the gateway is not available.
        // Stripe's live/test keys AND webhook signing secret must be configured.
        if (! app(\App\Payments\PaymentGatewayManager::class)->isAvailable('stripe')) {
            return back()->withErrors([
                'payment_method' => 'Secure Stripe checkout is unavailable. Please contact support or try again later.',
            ]);
        }
        $this->checkout->selectStripePayment($request->user());
        $this->checkout->confirmReview($validated);
        $snapshot = $this->checkout->placeOrder($validated, $request->user());
        $order = Order::query()->findOrFail((int) $snapshot['id']);

        try {
            $handoff = $this->payments->initiateCheckout($order, (string) $validated['idempotency_key']);
        } catch (ValidationException $exception) {
            return redirect()->route('account.orders.show', $order)
                ->withErrors($exception->errors())
                ->with('checkout_payment_error', 'The order was saved, but Stripe could not start. You can retry payment from this order.');
        } catch (PaymentGatewayException $exception) {
            return redirect()->route('account.orders.show', $order)
                ->withErrors(['payment_method' => $exception->getMessage()])
                ->with('checkout_payment_error', 'Your order was saved but Stripe checkout did not open. Retry payment from your order.');
        }

        if ($handoff->requiresRedirect()) {
            return redirect()->away((string) $handoff->url);
        }

        return redirect()->route('account.orders.show', $order)
            ->with('checkout_payment_error', 'The order is pending payment. Open Pay for Order to complete your secure Stripe payment.');
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
            return redirect()->route('cart.index')->with('status', 'Review your cart and resolve any product configuration issues before checkout.');
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
