@php
    $address = $state['shipping_address']['address'] ?? [];
    $selectedId = $state['shipping_address']['saved_address_id'] ?? null;
    $oldChoice = old('address_choice');
    $choice = $oldChoice === 'saved' && old('saved_address_id')
        ? 'saved:'.old('saved_address_id')
        : old('address_choice', $selectedId ? 'saved:'.$selectedId : 'new');
    $countries = app(\App\Services\Storefront\AddressGeography::class)->countries();
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <x-storefront.checkout.panel title="Shipping address" description="Where should we deliver your order?">
        <form class="nps-form np-checkout__form" method="POST" novalidate data-single-submit action="{{ route('checkout.shipping-address.store') }}" x-data="{
            addressChoice: @js($choice),
            remote: @js(! empty($summary['rural_surcharge_details'])),
            checkSequence: 0,
            async checkRemote() {
                const sequence = ++this.checkSequence;
                const isSaved = this.addressChoice.startsWith('saved:');
                const selected = this.$refs.addressChoiceSelect.selectedOptions[0];
                const postal = isSaved ? (selected?.dataset?.postal || '') : (this.$el.querySelector('[name=postal_code]')?.value || '');
                const country = isSaved ? (selected?.dataset?.country || '') : (this.$refs.remoteCountry?.value || '');
                const city = isSaved ? (selected?.dataset?.city || '') : (this.$el.querySelector('[name=city]')?.value || '');
                const state = isSaved ? (selected?.dataset?.state || '') : (this.$el.querySelector('[name=state]')?.value || '');
                this.remote = false;
                if (postal.trim().length < 3 || !country) return;
                try {
                    const response = await fetch(@js(route('checkout.remote-area-status')), {
                        method: 'POST', credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token())},
                        body: JSON.stringify({postal_code: postal, country: country, city: city, state: state})
                    });
                    const result = response.ok ? await response.json() : null;
                    if (sequence === this.checkSequence) this.remote = Boolean(result?.remote);
                } catch (error) { if (sequence === this.checkSequence) this.remote = false; }
            }
        }">
            @csrf
            <div class="nps-field">
                <label for="address_choice" class="nps-label">Use a saved address</label>
                <select name="address_choice" id="address_choice" x-ref="addressChoiceSelect" x-model="addressChoice" @change="checkRemote()">
                    <option value="new">Add a new address</option>
                    @foreach($savedShippingAddresses as $saved)
                        <option value="saved:{{ $saved['id'] }}" data-postal="{{ $saved['postal_code'] ?? '' }}" data-country="{{ $saved['country'] ?? '' }}" data-city="{{ $saved['city'] ?? '' }}" data-state="{{ $saved['state'] ?? '' }}">{{ $saved['label'] }} — {{ implode(', ', $saved['lines']) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="nps-grid" x-show="addressChoice === 'new'" x-cloak
                @input.debounce.650ms="if (['postal_code', 'city'].includes($event.target.name)) checkRemote()"
                @change="if (['postal_code', 'city', 'country'].includes($event.target.name)) checkRemote()">
                <x-storefront.checkout.form-field label="First name" name="first_name" :value="$address['first_name'] ?? $state['information']['first_name'] ?? ''" required />
                <x-storefront.checkout.form-field label="Last name" name="last_name" :value="$address['last_name'] ?? $state['information']['last_name'] ?? ''" required />
                <x-storefront.checkout.form-field label="Company" name="company_name" :value="$address['company_name'] ?? ''" full />
                <div class="nps-field nps-full"><label class="nps-label" for="country">Country / Region <span class="nps-required">*</span></label>
                    <select id="country" name="country" x-ref="remoteCountry" x-bind:required="addressChoice === 'new'" aria-invalid="{{ $errors->has('country') ? 'true' : 'false' }}"><option value="">Select country</option>
                        @foreach($countries as $country)<option value="{{ $country->country_name }}" @selected(old('country', $address['country'] ?? 'United Kingdom') === $country->country_name)>{{ $country->country_name }}</option>@endforeach
                    </select>
                    @error('country')
                        <p class="nps-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <x-storefront.checkout.form-field label="Address line 1" name="address_line_1" :value="$address['address_line_1'] ?? ''" full required />
                <x-storefront.checkout.form-field label="Address line 2" name="address_line_2" :value="$address['address_line_2'] ?? ''" full />
                <x-storefront.checkout.form-field label="Town / City" name="city" :value="$address['city'] ?? ''" required />
                <x-storefront.checkout.form-field label="Postcode" name="postal_code" :value="$address['postal_code'] ?? ''" required />
                <input type="hidden" name="state" value="{{ old('state', $address['state'] ?? '') }}">
                <input type="hidden" name="phone" value="{{ $address['phone'] ?? $state['information']['phone'] ?? '' }}">
                <input type="hidden" name="email" value="{{ $address['email'] ?? $state['information']['email'] ?? '' }}">
            </div>
            <div class="np-checkout__notice np-checkout__remote" x-show="remote" x-cloak role="status" aria-live="polite"><span class="np-checkout__pin" aria-hidden="true">●</span><div><strong>Remote-area delivery</strong><p>Your address is in a remote delivery area. An additional delivery charge applies.</p></div></div>
            <label class="np-checkout__check" x-show="addressChoice === 'new'"><input type="checkbox" name="save_to_account" value="1"><span>Save this address to my account</span></label>
            <div class="np-checkout__actions"><a href="{{ route('checkout.information') }}" class="np-checkout__back">← &nbsp; Back to contact</a><button class="np-checkout__button" type="submit">Continue to billing &nbsp; →</button></div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
