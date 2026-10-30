@props([
    'countryField' => 'country',
    'regionField' => 'state',
    'countryValue' => 'United Kingdom',
    'regionValue' => '',
    'regionLabel' => null,
    'variant' => 'checkout',
])
@php
    $countries = app(\App\Services\Storefront\AddressGeography::class)->countries();
    $countryValue = old($countryField, $countryValue);
    $regionValue = old($regionField, $regionValue);
    $isBulk = $variant === 'bulk';
    $isReferral = $variant === 'referral';
    $isAccount = $variant === 'account';
    $fieldClass = $isBulk ? 'bulk-quote-field bulk-quote-half' : ($isReferral ? 'np-referral-field' : '');
    $selectClass = $isBulk || $isReferral ? '' : ($isAccount
        ? 'h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10'
        : 'mt-2 h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-blue focus:bg-white');
    $labelClass = $isBulk || $isReferral ? '' : ($isAccount ? 'mb-2 block text-sm font-black text-slate-800' : 'text-sm font-black text-brand-ink');
@endphp
<div data-address-geography data-regions-url="{{ route('address.regions', ['iso' => '__ISO__']) }}" data-initial-region="{{ $regionValue }}" class="{{ $fieldClass }}" style="font-family:inherit">
    <label for="{{ $countryField }}" class="{{ $labelClass }}">Country <span class="{{ $isBulk ? 'bulk-quote-req' : 'text-brand-red' }}">*</span></label>
    <select id="{{ $countryField }}" name="{{ $countryField }}" class="{{ $selectClass }}" data-geo-country required style="font-family:var(--np-font-body,inherit)" autocomplete="country-name" aria-invalid="{{ $errors->has($countryField) ? 'true' : 'false' }}">
        <option value="">Select country</option>
        @foreach($countries as $country)
            <option value="{{ $country->country_name }}" data-iso="{{ $country->iso_code }}" data-region-label="{{ $country->region_label }}" data-has-regions="{{ $country->has_regions ? 1 : 0 }}" @selected((string) $countryValue === (string) $country->country_name)>{{ $country->country_name }}</option>
        @endforeach
    </select>
    @error($countryField)<p class="{{ $isBulk ? 'bulk-quote-field-error nps-error' : 'mt-2 text-xs font-semibold text-brand-red' }}" role="alert">{{ $message }}</p>@enderror
</div>
<div data-address-region-for="{{ $countryField }}" @if($isBulk) data-geo-region-freeform @endif class="{{ $fieldClass }}" style="font-family:inherit">
    <label for="{{ $regionField }}" class="{{ $labelClass }}" data-geo-region-label>{{ $regionLabel ?: 'State / Province' }} @if($isBulk)<span class="bulk-quote-optional">(optional)</span>@elseif(! $regionLabel)(optional)@endif</label>
    @unless($isBulk)
        <select id="{{ $regionField }}-select" data-geo-region-select data-region-name="{{ $regionField }}" class="{{ $selectClass }}" style="font-family:var(--np-font-body,inherit)" hidden disabled aria-label="State / Province">
            <option value="">Select (optional)</option>
        </select>
    @endunless
    <input id="{{ $regionField }}" name="{{ $regionField }}" data-geo-region-input value="{{ $regionValue }}" maxlength="120" class="{{ $selectClass }}" style="font-family:var(--np-font-body,inherit)" placeholder="{{ $isBulk ? 'County / State (optional)' : 'State / Province (optional)' }}" autocomplete="address-level1" aria-invalid="{{ $errors->has($regionField) ? 'true' : 'false' }}">
    @error($regionField)<p class="{{ $isBulk ? 'bulk-quote-field-error nps-error' : 'mt-2 text-xs font-semibold text-brand-red' }}" role="alert">{{ $message }}</p>@enderror
</div>
@once
    <style>[data-geo-region-select][hidden],[data-geo-region-input][hidden]{display:none!important}</style>
    <script src="{{ asset('js/address-geography.js') }}" defer></script>
@endonce
