@php
    $selectedShippingId = $state['shipping_address']['saved_address_id'] ?? null;
    $oldAddressChoice = old('address_choice');
    $oldSavedAddressId = old('saved_address_id');
    $defaultAddressChoice = $oldAddressChoice === 'saved' && $oldSavedAddressId
        ? 'saved:'.$oldSavedAddressId
        : old('address_choice', $selectedShippingId ? 'saved:'.$selectedShippingId : (count($savedShippingAddresses) > 0 ? 'saved:'.$savedShippingAddresses[0]['id'] : 'new'));
@endphp

<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Shipping Address" description="Use a saved shipping address or add a new delivery address for this order." :summary="$summary">
    <x-storefront.checkout.panel title="Shipping Address" description="Choose where the order should be delivered. Rural ZIP/postal surcharges are checked automatically after this step.">
        <form method="POST" action="{{ route('checkout.shipping-address.store') }}" class="grid gap-6" x-data="{ addressChoice: @js($defaultAddressChoice) }">
            @csrf

            @if (count($savedShippingAddresses) > 0)
                <div>
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Saved shipping addresses</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Choose one to continue quickly, or add a new address below.</p>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach ($savedShippingAddresses as $address)
                            @php($choiceValue = 'saved:'.$address['id'])
                            <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="address_choice" value="{{ $choiceValue }}" class="mt-1" x-model="addressChoice">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <strong class="text-brand-ink">{{ $address['label'] }}</strong>
                                            @if ($address['is_default'])
                                                <span class="rounded-full bg-brand-red px-2 py-0.5 text-[10px] font-black uppercase text-white">Default</span>
                                            @endif
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-black uppercase text-slate-500">{{ $address['type'] }}</span>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{!! implode('<br>', array_map('e', $address['lines'])) !!}</p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <label class="mb-5 flex items-center gap-3 text-sm font-black text-brand-ink">
                    <input type="radio" name="address_choice" value="new" x-model="addressChoice">
                    Add a new shipping address
                </label>

                <div class="grid gap-5 sm:grid-cols-2" x-show="addressChoice === 'new'" x-cloak>
                    <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" :value="$state['shipping_address']['address']['first_name'] ?? $state['information']['first_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" :value="$state['shipping_address']['address']['last_name'] ?? $state['information']['last_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Company / Team" name="company_name" placeholder="Optional" :value="$state['shipping_address']['address']['company_name'] ?? ''" full />
                    <x-storefront.checkout.form-field label="Street address" name="address_line_1" placeholder="Street address" :value="$state['shipping_address']['address']['address_line_1'] ?? ''" full required />
                    <x-storefront.checkout.form-field label="Apartment / suite" name="address_line_2" placeholder="Optional" :value="$state['shipping_address']['address']['address_line_2'] ?? ''" />
                    <x-storefront.checkout.form-field label="City" name="city" placeholder="City" :value="$state['shipping_address']['address']['city'] ?? ''" required />
                    <x-storefront.checkout.form-field label="State / Province" name="state" placeholder="State" :value="$state['shipping_address']['address']['state'] ?? ''" required />
                    <x-storefront.checkout.form-field label="ZIP / Postal code" name="postal_code" placeholder="ZIP" :value="$state['shipping_address']['address']['postal_code'] ?? ''" required hint="If this ZIP/postal code matches an admin rural-area rule, the surcharge is added automatically." />
                    <x-storefront.checkout.form-field label="Country" name="country" :value="$state['shipping_address']['address']['country'] ?? 'United States'" :options="['United States' => 'United States', 'Canada' => 'Canada']" required />
                    <x-storefront.checkout.form-field label="Delivery phone" name="phone" type="tel" placeholder="Delivery phone" :value="$state['shipping_address']['address']['phone'] ?? $state['information']['phone'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Delivery email" name="email" type="email" placeholder="Delivery email" :value="$state['shipping_address']['address']['email'] ?? $state['information']['email'] ?? ''" />
                    <x-storefront.checkout.form-field label="Delivery instruction" name="delivery_instruction" placeholder="Gate code, school office, club house, delivery contact..." :value="$state['shipping_address']['address']['delivery_instruction'] ?? ''" textarea full />
                </div>

                @auth
                    <label class="mt-5 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-700" x-show="addressChoice === 'new'" x-cloak>
                        <input type="checkbox" name="save_to_account" value="1" class="mt-1">
                        Save this shipping address to my account for future orders.
                    </label>
                @endauth
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-bold leading-6 text-brand-navy">
                Rural/remote ZIP rules are managed from the admin panel. Checkout totals are recalculated by the server before payment.
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.information') }}">Back</a>
                <button class="btn btn-red" type="submit">Continue to Billing</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
