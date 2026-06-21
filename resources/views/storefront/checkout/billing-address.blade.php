<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Billing Address Selection" description="Choose whether billing address is the same as shipping or add a separate billing address." :summary="$summary">
    <x-storefront.checkout.panel title="Billing Address" description="Select the billing address for payment verification and invoice records.">
        <form method="POST" action="{{ route('checkout.billing-address.store') }}" class="grid gap-6">
            @csrf

            <label class="flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-900">
                <input type="checkbox" name="same_as_shipping" value="1" class="mt-1" @checked(($state['billing_address']['same_as_shipping'] ?? true))>
                <span><strong>Billing address is same as shipping address.</strong><br><span class="font-semibold text-green-800">Recommended when the cardholder and shipping recipient are the same.</span></span>
            </label>

            @if (isset($state['shipping_address']['address']))
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">
                    <strong class="text-brand-ink">Current shipping address:</strong>
                    {{ $state['shipping_address']['label'] ?? 'Shipping address' }},
                    {{ $state['shipping_address']['address']['address_line_1'] ?? '' }},
                    {{ $state['shipping_address']['address']['city'] ?? '' }}
                    {{ $state['shipping_address']['address']['state'] ?? '' }}
                    {{ $state['shipping_address']['address']['postal_code'] ?? '' }}.
                </div>
            @endif

            @if (count($savedAddresses) > 0)
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Saved billing addresses</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach ($savedAddresses as $address)
                            <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="address_choice" value="saved:{{ $address['id'] }}" class="mt-1" @checked(($state['billing_address']['saved_address_id'] ?? null) === $address['id'])>
                                    <div>
                                        <strong class="text-brand-ink">{{ $address['label'] }}</strong>
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
                    <input type="radio" name="address_choice" value="new" @checked(! isset($state['billing_address']['saved_address_id']))>
                    Add a separate billing address
                </label>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" :value="$state['billing_address']['address']['first_name'] ?? $state['information']['first_name'] ?? ''" />
                    <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" :value="$state['billing_address']['address']['last_name'] ?? $state['information']['last_name'] ?? ''" />
                    <x-storefront.checkout.form-field label="Company name" name="company_name" placeholder="Optional" :value="$state['billing_address']['address']['company_name'] ?? ''" full />
                    <x-storefront.checkout.form-field label="Street address" name="address_line_1" placeholder="Billing street address" :value="$state['billing_address']['address']['address_line_1'] ?? ''" full />
                    <x-storefront.checkout.form-field label="Apartment / suite" name="address_line_2" placeholder="Optional" :value="$state['billing_address']['address']['address_line_2'] ?? ''" />
                    <x-storefront.checkout.form-field label="City" name="city" placeholder="City" :value="$state['billing_address']['address']['city'] ?? ''" />
                    <x-storefront.checkout.form-field label="State" name="state" placeholder="State" :value="$state['billing_address']['address']['state'] ?? ''" />
                    <x-storefront.checkout.form-field label="ZIP code" name="postal_code" placeholder="ZIP" :value="$state['billing_address']['address']['postal_code'] ?? ''" />
                    <x-storefront.checkout.form-field label="Country" name="country" :value="$state['billing_address']['address']['country'] ?? 'United States'" :options="['United States' => 'United States', 'Canada' => 'Canada']" />
                    <x-storefront.checkout.form-field label="Phone" name="phone" type="tel" placeholder="Billing phone" :value="$state['billing_address']['address']['phone'] ?? $state['information']['phone'] ?? ''" />
                    <x-storefront.checkout.form-field label="Email" name="email" type="email" placeholder="Billing email" :value="$state['billing_address']['address']['email'] ?? $state['information']['email'] ?? ''" />
                </div>

                @auth
                    <label class="mt-5 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="save_to_account" value="1" class="mt-1">
                        Save this billing address to my account.
                    </label>
                @endauth
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.shipping-address') }}">Back</a>
                <button class="btn btn-red" type="submit">Continue to Shipping Method</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
