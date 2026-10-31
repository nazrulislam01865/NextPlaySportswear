@php
    $info = $state['information'] ?? [];
    $ship = $state['shipping_address']['address'] ?? [];
    $shipments = $state['shipping_address']['shipments'] ?? [];
    $bill = $state['billing_address']['address'] ?? [];
    $same = (bool) old('billing_same_as_shipping', $state['billing_address']['same_as_shipping'] ?? true);
    $countries = app(\App\Services\Storefront\AddressGeography::class)->countries();
    $address = ($same ? $ship : $bill) ?: $ship;
    $first = $same ? ($ship['first_name'] ?? $info['first_name'] ?? '') : ($bill['first_name'] ?? '');
    $last = $same ? ($ship['last_name'] ?? $info['last_name'] ?? '') : ($bill['last_name'] ?? '');
    $email = $same ? ($info['email'] ?? '') : ($bill['email'] ?? $info['email'] ?? '');
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary" :order-idempotency-key="$orderIdempotencyKey">
    <form class="nps-form np-checkout__form np-checkout__stage-form np-checkout__review-form" id="np-checkout-final" method="POST" data-single-submit novalidate action="{{ route('checkout.place-order.submit') }}" data-checkout-review>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
        <input type="hidden" name="confirm_details" value="1">
        <input type="hidden" name="payment_method" value="stripe">
        <input type="hidden" name="billing_same_as_shipping" id="np-billing-same" value="{{ $same ? 1 : 0 }}">
        @if($errors->any())
            <div class="np-checkout__form-alert" role="alert" tabindex="-1">Please review the highlighted details and try again. {{ $errors->first('checkout') ?: '' }}</div>
        @endif
        <x-storefront.checkout.panel title="1. Review your order" description="Please check your products, delivery details and estimated timelines before payment.">
            <div class="np-checkout__review-intro"><div class="np-checkout__info-line"><span aria-hidden="true">ⓘ</span><div>Each product has its own artwork approval and shipment tracking.</div></div></div>
            <div class="np-checkout__review-items">
                @foreach(($summary['items'] ?? []) as $index => $item)
                    @php
                        $line = $summary['fulfillment_lines'][$index] ?? [];
                        $delivery = $shipments[($state['shipping_address']['shipment_plan'] ?? 'separate') === 'together' ? 0 : $index]['address'] ?? $ship;
                        $shipping = $line['shipping'] ?? [];
                        $minimumDays = max(0, (int) ($line['estimated_minimum_days'] ?? 0));
                        $maximumDays = max($minimumDays, (int) ($line['estimated_maximum_days'] ?? 0));
                        $productionDays = max(0, (int) data_get($line, 'production.minimum_days', 0));
                    @endphp
                    <article class="np-checkout__review-product">
                        <div class="np-checkout__review-product-main">
                            <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['title'] ?? 'Product' }}" loading="lazy">
                            <div><strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Custom product' }}</strong><p>{{ $item['customization']['design_option'] ?? 'Custom design' }}</p><p>Qty: {{ (int) ($item['quantity'] ?? 1) }}</p><b>${{ number_format((float) ($item['line_subtotal'] ?? 0) + (float) ($item['customization_total'] ?? 0), 2) }}</b></div>
                        </div>
                        <div class="np-checkout__review-product-meta">
                            <div><span>Delivery to</span><strong><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/></svg>{{ trim(($delivery['first_name'] ?? '').' '.($delivery['last_name'] ?? '')) }}</strong><p>{{ $delivery['city'] ?? '' }} {{ $delivery['postal_code'] ?? '' }}<br>{{ $delivery['country'] ?? '' }}</p></div>
                            <div><span>Shipping method</span><strong><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 7h12v10H2zM14 10h4l4 4v3h-8z"/></svg>{{ $shipping['label'] ?? 'Selected shipping' }}</strong><p><b>{{ $shipping['display_amount'] ?? '$'.number_format((float) ($shipping['amount'] ?? 0), 2) }}</b></p></div>
                            <div><span>Estimated timeline<br>(approval dependent)</span><strong><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 10h18"/></svg>Dispatch: {{ now()->addWeekdays($productionDays)->format('j M Y') }}</strong><p>Arrival: {{ now()->addWeekdays($minimumDays)->format('j M') }}–{{ now()->addWeekdays($maximumDays)->format('j M Y') }}</p><p>Production starts after artwork approval.</p></div>
                        </div>
                        <a class="np-checkout__edit-item" href="{{ route('checkout.shipping-address') }}">Edit</a>
                    </article>
                @endforeach
            </div>
        </x-storefront.checkout.panel>

        <x-storefront.checkout.panel title="2. Billing details (payer)" description="This is the cardholder and billing address for payment.">
            <label class="np-checkout__checkbox np-checkout__billing-use"><input type="checkbox" data-use-shipping-address @checked($same)><span><strong>Use Shipment 1 address</strong><small>Same as {{ trim(($ship['first_name'] ?? '').' '.($ship['last_name'] ?? '')) }}'s delivery address ({{ $ship['city'] ?? '' }} {{ $ship['postal_code'] ?? '' }})</small></span></label>
            <div class="np-checkout__billing-grid" data-billing-fields data-primary-address="{{ json_encode([ 'billing_first_name' => $ship['first_name'] ?? $info['first_name'] ?? '', 'billing_last_name' => $ship['last_name'] ?? $info['last_name'] ?? '', 'billing_email' => $info['email'] ?? '', 'billing_country' => $ship['country'] ?? 'United Kingdom', 'billing_address_line_1' => $ship['address_line_1'] ?? '', 'billing_address_line_2' => $ship['address_line_2'] ?? '', 'billing_city' => $ship['city'] ?? '', 'billing_postal_code' => $ship['postal_code'] ?? '' ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
                <x-storefront.checkout.form-field label="First name" name="billing_first_name" :value="$first" required />
                <x-storefront.checkout.form-field label="Last name" name="billing_last_name" :value="$last" required />
                <x-storefront.checkout.form-field label="Email address" name="billing_email" type="email" :value="$email" required />
                <div class="nps-field"><label class="nps-label" for="billing_country">Country / Region <span class="nps-required">*</span></label><select id="billing_country" name="billing_country" data-nps-dropdown="country" required><option value="">Select country</option>@foreach($countries as $country)<option value="{{ $country->country_name }}" @selected(old('billing_country', $address['country'] ?? 'United Kingdom') === $country->country_name)>{{ $country->country_name }}</option>@endforeach</select>@error('billing_country')<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                <x-storefront.checkout.form-field label="Address line 1" name="billing_address_line_1" :value="$address['address_line_1'] ?? ''" required />
                <x-storefront.checkout.form-field label="Address line 2" name="billing_address_line_2" :value="$address['address_line_2'] ?? ''" />
                <x-storefront.checkout.form-field label="Town / City" name="billing_city" :value="$address['city'] ?? ''" required />
                <x-storefront.checkout.form-field label="Postcode" name="billing_postal_code" :value="$address['postal_code'] ?? ''" required />
            </div>
        </x-storefront.checkout.panel>

        <div class="np-checkout__review-completion">
            <label class="np-checkout__checkbox np-checkout__terms"><input type="checkbox" name="terms" value="1" id="np-checkout-terms" @checked((bool) old('terms', false)) required><span>I agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms &amp; Conditions</a> and confirm that all details are correct. I understand that production starts after separate artwork approval for each product.</span></label>
            @error('terms')<p class="nps-error" role="alert">{{ $message }}</p>@enderror
            <div class="np-checkout__form-end np-checkout__stage-actions">
                <a class="np-checkout__back" href="{{ route('checkout.shipping-address') }}">← Back to shipping</a>
                <button type="submit" class="np-checkout__button" data-checkout-pay-button @if($summary['remote_price_missing'] ?? false) data-price-blocked="true" disabled @endif>Pay ${{ number_format((float) ($summary['total'] ?? 0), 2) }} &amp; Place Order <span aria-hidden="true">→</span></button>
            </div>
            <p class="np-checkout__fine np-checkout__center">🔒 Secure checkout — card details are collected on Stripe's secure payment page.</p>
        </div>
    </form>
</x-storefront.checkout.shell>
