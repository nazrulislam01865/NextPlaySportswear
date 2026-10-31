@php
    $billingSame = (bool) old('same_as_shipping', $state['billing_address']['same_as_shipping'] ?? true);
    $billing = $state['billing_address']['address'] ?? [];
    $ship = $state['shipping_address']['address'] ?? [];
    $savedId = $state['billing_address']['saved_address_id'] ?? null;
    $oldChoice = old('address_choice');
    $addressChoice = $oldChoice === 'saved' && old('saved_address_id')
        ? 'saved:'.old('saved_address_id')
        : old('address_choice', $savedId ? 'saved:'.$savedId : 'new');
    $countries = app(\App\Services\Storefront\AddressGeography::class)->countries();
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <x-storefront.checkout.panel title="Billing address" description="Choose the address to appear on your invoice.">
        <form class="nps-form np-checkout__form" method="POST" novalidate data-single-submit action="{{ route('checkout.billing-address.store') }}" x-data="{ same: @js($billingSame), addressChoice: @js($addressChoice) }">
            @csrf
            <input type="hidden" name="same_as_shipping" :value="same ? '1' : '0'">
            <label class="np-checkout__choice" :class="same ? 'is-selected' : ''"><input type="radio" name="billing_mode" value="same" :checked="same" @change="same=true"><span><strong>Same as shipping address</strong><span x-show="same" class="np-checkout__address-preview">{{ trim(($ship['first_name'] ?? '').' '.($ship['last_name'] ?? '')) }}<br>{{ $ship['address_line_1'] ?? '' }}<br>{{ trim(($ship['city'] ?? '').', '.($ship['postal_code'] ?? ''), ', ') }}<br>{{ $ship['country'] ?? '' }}</span></span></label>
            <label class="np-checkout__choice" :class="!same ? 'is-selected' : ''"><input type="radio" name="billing_mode" value="different" :checked="!same" @change="same=false"><strong>Use a different billing address</strong></label>
            <div class="np-checkout__extra-address" x-show="!same" x-cloak>
                <div class="nps-field"><label class="nps-label" for="address_choice">Use a saved billing address</label><select id="address_choice" name="address_choice" x-model="addressChoice"><option value="new">Add a new address</option>
@foreach($savedBillingAddresses as $saved)<option value="saved:{{ $saved['id'] }}">{{ $saved['label'] }} — {{ implode(', ', $saved['lines']) }}</option>
@endforeach
</select></div>
                <div class="nps-grid" x-show="addressChoice === 'new'" x-cloak>
                    <x-storefront.checkout.form-field label="First name" name="first_name" :value="$billing['first_name'] ?? $state['information']['first_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Last name" name="last_name" :value="$billing['last_name'] ?? $state['information']['last_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Address line 1" name="address_line_1" :value="$billing['address_line_1'] ?? ''" full required />
                    <x-storefront.checkout.form-field label="Address line 2" name="address_line_2" :value="$billing['address_line_2'] ?? ''" full />
                    <x-storefront.checkout.form-field label="Town / City" name="city" :value="$billing['city'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Postcode" name="postal_code" :value="$billing['postal_code'] ?? ''" required />
                    <div class="nps-field nps-full"><label class="nps-label" for="country">Country / Region <span class="nps-required">*</span></label><select name="country" id="country"><option value="">Select country</option>
@foreach($countries as $country)<option value="{{ $country->country_name }}" @selected(old('country', $billing['country'] ?? $ship['country'] ?? 'United Kingdom') === $country->country_name)>{{ $country->country_name }}</option>
@endforeach
</select>
@error('country')<p class="nps-error">{{ $message }}</p>
@enderror
</div>
                    <input type="hidden" name="state" value="{{ $billing['state'] ?? '' }}"><input type="hidden" name="phone" value="{{ $state['information']['phone'] ?? '' }}"><input type="hidden" name="email" value="{{ $state['information']['email'] ?? '' }}">
                </div>
                <label class="np-checkout__check" x-show="addressChoice === 'new'"><input type="checkbox" name="save_to_account" value="1"><span>Save this billing address to my account</span></label>
            </div>
            <div class="np-checkout__divider"></div>
            <h3 class="np-checkout__subheading">Invoice details</h3>
            <p class="np-checkout__subdescription">Add business details if needed.</p>
            <div class="nps-grid">
                <x-storefront.checkout.form-field label="Company name" name="invoice_company" placeholder="Enter company name" :value="$state['billing_address']['invoice_details']['company_name'] ?? ''" />
                <x-storefront.checkout.form-field label="VAT / Tax number" name="invoice_tax_number" placeholder="Enter VAT or tax number" :value="$state['billing_address']['invoice_details']['tax_number'] ?? ''" />
            </div>
            <p class="np-checkout__invoice-info">● &nbsp; Your invoice will be sent to {{ $state['information']['email'] ?? auth()->user()?->email }}.</p>
            <div class="np-checkout__actions"><a class="np-checkout__back" href="{{ route('checkout.shipping-address') }}">← &nbsp; Back to shipping</a><button type="submit" class="np-checkout__button">Continue to review &nbsp; →</button></div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
