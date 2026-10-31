@php
    $info = $state['information'] ?? [];
    $name = trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? ''));
    if ($name === '' && auth()->user()) $name = auth()->user()->name ?? '';
    $parts = preg_split('/\s+/', trim($name), 2);
    $currentPhone = old('phone', $info['phone'] ?? auth()->user()?->phone ?? '');
    $phoneDial = '+44';
    $phoneLocal = trim((string) $currentPhone);
    if (preg_match('/^(\+\d{1,4})\s+(.+)$/', (string) $currentPhone, $phoneMatch)) {
        $phoneDial = $phoneMatch[1];
        $phoneLocal = $phoneMatch[2];
    }
    $callingCodes = \App\Models\CountryCallingCode::query()->active()->ordered()->get(['iso_code', 'country_name', 'dial_code']);
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <x-storefront.checkout.panel title="Contact information" description="We'll use these details for order and delivery updates.">
        <form class="nps-form np-checkout__form" data-single-submit method="POST" action="{{ route('checkout.information.store') }}" novalidate x-data="{ phoneDial: @js($phoneDial), phoneLocal: @js($phoneLocal) }">
            @csrf
            <input type="hidden" name="contact_choice" value="new">
            <div class="nps-grid">
                <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" autocomplete="given-name" :value="$info['first_name'] ?? ($parts[0] ?? '')" required />
                <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" autocomplete="family-name" :value="$info['last_name'] ?? ($parts[1] ?? '')" required />
                <x-storefront.checkout.form-field label="Email address" name="email" type="email" autocomplete="email" :value="$info['email'] ?? auth()->user()?->email" full required />
                <div class="nps-field nps-full"><label class="nps-label" for="checkout-phone-local">Phone number <span class="nps-required">*</span></label>
                    <div class="np-checkout__phone"><select class="np-checkout__phone-code" aria-label="Country calling code" x-model="phoneDial">
                        @foreach($callingCodes as $code)<option value="{{ $code->dial_code }}" @selected($code->dial_code === $phoneDial)>{{ $code->flagEmoji() }} {{ $code->dial_code }}</option>
@endforeach
                    </select><input type="tel" class="np-checkout__phone-input" id="checkout-phone-local" x-model="phoneLocal" autocomplete="tel-national" placeholder="7700 900123" required></div>
                    <input type="hidden" name="phone" :value="phoneDial + ' ' + phoneLocal">
                    @error('phone')<p class="nps-error" role="alert">{{ $message }}</p>
@enderror
                </div>
            </div>
            <label class="np-checkout__check"><input type="checkbox" name="save_to_account" value="1" @checked((bool) old('save_to_account', false))><span>Save these details to my account</span></label>
            <div class="np-checkout__divider"></div>
            <x-storefront.checkout.form-field label="Order note" name="order_note" placeholder="Add any delivery notes or special instructions..." :value="$info['order_note'] ?? ''" textarea />
            <div class="np-checkout__actions"><a href="{{ route('cart.index') }}" class="np-checkout__back">← &nbsp; Back to cart</a><button class="np-checkout__button" type="submit">Continue to shipping &nbsp; →</button></div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
