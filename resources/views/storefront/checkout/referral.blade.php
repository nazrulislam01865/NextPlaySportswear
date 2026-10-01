<x-layouts.storefront :seo="$seo">
    @php
        $offer = (array) ($summary['referral_offer'] ?? []);
        $contact = (array) ($state['information'] ?? []);
        $shipping = (array) data_get($state, 'shipping_address.address', []);
        $billingSame = old('billing_same_as_shipping', true);
        $defaultPayment = old('payment_method', collect($paymentOptions)->first()['code'] ?? '');
    @endphp

    <section class="np-referral-checkout-page">
        <div class="site-container">
            <nav class="np-referral-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('cart.index') }}">Basket</a><span>/</span><strong>Checkout</strong></nav>

            <div class="np-referral-checkout-top">
                <h1>SECURE CHECKOUT</h1>
                <x-storefront.referral.stepper :current="2" />
            </div>

            @if (session('status'))<div class="np-referral-form-alert np-referral-form-alert--success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="np-referral-form-alert">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('checkout.referral.store') }}" class="np-referral-checkout-grid" novalidate data-single-submit>
                @csrf
                <div class="np-referral-checkout-main">
                    <section class="np-referral-checkout-panel">
                        <div class="np-referral-checkout-panel__heading"><h2>CONTACT</h2><p>We'll send your order confirmation to this email address.</p></div>
                        <label class="np-referral-field np-referral-field--full">Email address <span>*</span><input type="email" name="email" value="{{ old('email', $contact['email'] ?? auth()->user()?->email) }}" placeholder="you@example.com" autocomplete="email" required></label>
                    </section>

                    <section class="np-referral-checkout-panel">
                        <div class="np-referral-checkout-panel__heading np-referral-checkout-panel__heading--row"><h2>DELIVERY ADDRESS</h2><span>Secure delivery details</span></div>
                        <div class="np-referral-fields-grid">
                            <label class="np-referral-field">First name <span>*</span><input type="text" name="first_name" value="{{ old('first_name', $shipping['first_name'] ?? data_get($contact, 'first_name', str(auth()->user()?->name)->before(' '))) }}" placeholder="First name" autocomplete="given-name" required></label>
                            <label class="np-referral-field">Last name <span>*</span><input type="text" name="last_name" value="{{ old('last_name', $shipping['last_name'] ?? data_get($contact, 'last_name', str(auth()->user()?->name)->after(' '))) }}" placeholder="Last name" autocomplete="family-name" required></label>
                            <label class="np-referral-field">Address line 1 <span>*</span><input type="text" name="address_line_1" value="{{ old('address_line_1', $shipping['address_line_1'] ?? '') }}" placeholder="House name or number and street" autocomplete="address-line1" required></label>
                            <label class="np-referral-field">Apartment, suite, etc. (optional)<input type="text" name="address_line_2" value="{{ old('address_line_2', $shipping['address_line_2'] ?? '') }}" placeholder="Apartment, suite, etc." autocomplete="address-line2"></label>
                            <label class="np-referral-field">City / Town <span>*</span><input type="text" name="city" value="{{ old('city', $shipping['city'] ?? '') }}" placeholder="City or town" autocomplete="address-level2" required></label>
                            <label class="np-referral-field">Postcode <span>*</span><input type="text" name="postal_code" value="{{ old('postal_code', $shipping['postal_code'] ?? '') }}" placeholder="Postcode" autocomplete="postal-code" required></label>
                            <label class="np-referral-field">County / State<input type="text" name="state" value="{{ old('state', $shipping['state'] ?? '') }}" placeholder="County or state" autocomplete="address-level1"></label>
                            <label class="np-referral-field">Country <span>*</span><select name="country" autocomplete="country-name" required>@foreach (['United Kingdom', 'United States', 'Canada', 'Australia', 'New Zealand'] as $country)<option value="{{ $country }}" @selected(old('country', $shipping['country'] ?? 'United Kingdom') === $country)>{{ $country }}</option>@endforeach</select></label>
                        </div>
                    </section>

                    <section class="np-referral-checkout-panel">
                        <div class="np-referral-checkout-panel__heading"><h2>DELIVERY METHOD</h2><p>Select how you'd like to receive your order. The final delivery charge continues to use the delivery method configured for each product.</p></div>
                        <label class="np-referral-choice"><input type="radio" name="delivery_preference" value="standard" @checked(old('delivery_preference', 'standard') === 'standard')><span><strong>Standard delivery</strong><small>Delivery cost is calculated from your configured items.</small></span><b>To be confirmed<small>Calculated at review</small></b></label>
                        <label class="np-referral-choice"><input type="radio" name="delivery_preference" value="express" @checked(old('delivery_preference') === 'express')><span><strong>Express delivery</strong><small>We'll keep this preference with your checkout details.</small></span><b>To be confirmed<small>Calculated at review</small></b></label>
                    </section>

                    <section class="np-referral-checkout-panel">
                        <div class="np-referral-checkout-panel__heading np-referral-checkout-panel__heading--row"><h2>PAYMENT</h2><span>🔒 Secure payment</span></div>
                        @forelse ($paymentOptions as $index => $option)
                            @php($code = (string) ($option['code'] ?? ''))
                            <label class="np-referral-payment-choice">
                                <input type="radio" name="payment_method" value="{{ $code }}" @checked($defaultPayment === $code) required>
                                <span><strong>{{ $option['label'] ?? $option['title'] ?? 'Secure payment' }}</strong><small>{{ $option['instructions'] ?? 'Payment details are handled by the secure payment provider.' }}</small></span>
                            </label>
                            @if ($index === 0)
                                <div class="np-referral-card-visual" aria-label="Card details are collected securely after review">
                                    <label>Card number<input type="text" value="1234 5678 9012 3456" disabled></label>
                                    <label>Expiry date<input type="text" value="MM / YY" disabled></label>
                                    <label>Security code<input type="text" value="CVC" disabled></label>
                                    <p>Card details are never stored here; the secure provider collects them after review.</p>
                                </div>
                            @endif
                        @empty
                            <p class="np-referral-payment-unavailable">No payment method is currently available for this order total. Please contact support.</p>
                        @endforelse

                        <label class="np-referral-check np-referral-billing-toggle"><input type="checkbox" name="billing_same_as_shipping" value="1" @checked($billingSame) data-referral-billing-toggle><span>Billing address is the same as delivery address</span></label>

                        <div class="np-referral-billing-fields {{ $billingSame ? 'hidden' : '' }}" data-referral-billing-fields>
                            <h3>Billing address</h3>
                            <div class="np-referral-fields-grid">
                                <label class="np-referral-field">First name <span>*</span><input type="text" name="billing_first_name" value="{{ old('billing_first_name') }}"></label>
                                <label class="np-referral-field">Last name <span>*</span><input type="text" name="billing_last_name" value="{{ old('billing_last_name') }}"></label>
                                <label class="np-referral-field">Address line 1 <span>*</span><input type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1') }}"></label>
                                <label class="np-referral-field">Address line 2<input type="text" name="billing_address_line_2" value="{{ old('billing_address_line_2') }}"></label>
                                <label class="np-referral-field">City / Town <span>*</span><input type="text" name="billing_city" value="{{ old('billing_city') }}"></label>
                                <label class="np-referral-field">Postcode <span>*</span><input type="text" name="billing_postal_code" value="{{ old('billing_postal_code') }}"></label>
                                <label class="np-referral-field">County / State<input type="text" name="billing_state" value="{{ old('billing_state') }}"></label>
                                <label class="np-referral-field">Country <span>*</span><select name="billing_country">@foreach (['United Kingdom', 'United States', 'Canada', 'Australia', 'New Zealand'] as $country)<option value="{{ $country }}" @selected(old('billing_country', 'United Kingdom') === $country)>{{ $country }}</option>@endforeach</select></label>
                            </div>
                        </div>
                    </section>

                    <section class="np-referral-checkout-panel np-referral-review-panel">
                        <h2>REVIEW &amp; PLACE ORDER</h2>
                        <label class="np-referral-check"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required><span>I agree to the <a href="{{ route('terms') }}">Terms &amp; Conditions</a> <em>*</em></span></label>
                        <div class="np-referral-review-panel__actions"><a href="{{ route('cart.index') }}"><x-storefront.referral.icon name="arrow-left" :size="18" /> Back to basket</a><button type="submit" class="btn btn-secondary" @disabled(empty($paymentOptions))>CONTINUE TO REVIEW <x-storefront.referral.icon name="arrow-right" :size="20" /></button></div>
                    </section>
                </div>

                <aside class="np-referral-checkout-summary">
                    <h2>ORDER SUMMARY</h2>
                    <div class="np-referral-checkout-summary__body">
                        <div class="np-referral-checkout-summary__title"><strong>{{ $summary['quantity'] }} item{{ $summary['quantity'] === 1 ? '' : 's' }}</strong><a href="{{ route('cart.index') }}">Edit basket</a></div>
                        @foreach ($summary['items'] as $item)
                            @php($product = (array) ($item['product'] ?? []))
                            <div class="np-referral-checkout-mini-item"><img src="{{ $product['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $product['alt'] ?? $product['title'] ?? 'Product' }}" loading="lazy"><div><strong>{{ $product['title'] ?? 'NEXTPLAY product' }}</strong><p>Qty: {{ $item['quantity'] }}</p></div><b>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</b></div>
                        @endforeach
                        <div class="np-referral-summary-divider"></div>
                        <div class="np-referral-summary-row"><span>Items subtotal ({{ $summary['quantity'] }} items)</span><strong>${{ number_format((float) ($summary['subtotal'] + $summary['customization_total']), 2) }}</strong></div>
                        <div class="np-referral-summary-row np-referral-summary-row--reward np-referral-checkout-reward"><span><x-storefront.referral.icon name="gift" :size="24" /> <b>First order friend reward</b><small>Your friend's £5 offer is linked to this visit.<br><a href="{{ route('terms') }}">Offer terms</a></small></span><strong>{{ ($summary['referral_discount'] ?? 0) > 0 ? '−$'.number_format((float) $summary['referral_discount'], 2) : '$0.00' }}</strong></div>
                        <div class="np-referral-summary-row"><span>Delivery</span><span>Confirmed at review</span></div>
                        <div class="np-referral-summary-divider"></div>
                        <div class="np-referral-summary-total"><span>Estimated total<small>including configured charges</small></span><strong>${{ number_format((float) $summary['total'], 2) }}</strong></div>
                        <div class="np-referral-summary-info"><x-storefront.referral.icon name="info" :size="24" /><p>Your final total is validated server-side before the order is placed.</p></div>
                    </div>
                    <div class="np-referral-support-grid"><div><x-storefront.referral.icon name="truck" :size="30" /><span>Worldwide<br>shipping</span></div><div><x-storefront.referral.icon name="box" :size="30" /><span>Hassle-free<br>returns</span></div><div><x-storefront.referral.icon name="support" :size="30" /><span>Dedicated<br>support</span></div></div>
                </aside>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-referral-billing-toggle]');
            const fields = document.querySelector('[data-referral-billing-fields]');
            if (!toggle || !fields) return;
            const sync = () => fields.classList.toggle('hidden', toggle.checked);
            toggle.addEventListener('change', sync);
            sync();
        });
    </script>
</x-layouts.storefront>
