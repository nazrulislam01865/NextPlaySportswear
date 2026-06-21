<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Shipping Address Selection" description="Select a saved shipping address or add a new address for your custom sportswear order." :summary="$summary">
    <x-storefront.checkout.panel title="Shipping Address" description="Choose where the order should be delivered. Team orders can be shipped to a school, club, office, or home address.">
        <form method="POST" action="{{ route('checkout.shipping-address.store') }}" class="grid gap-6">
            @csrf

            @if (count($savedAddresses) > 0)
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Saved addresses</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach ($savedAddresses as $address)
                            <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="address_choice" value="saved:{{ $address['id'] }}" class="mt-1" @checked(($state['shipping_address']['saved_address_id'] ?? null) === $address['id'])>
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
                    <input type="radio" name="address_choice" value="new" @checked(! isset($state['shipping_address']['saved_address_id']))>
                    Add a new shipping address
                </label>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" :value="$state['shipping_address']['address']['first_name'] ?? $state['information']['first_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" :value="$state['shipping_address']['address']['last_name'] ?? $state['information']['last_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Company / Team" name="company_name" placeholder="Optional" :value="$state['shipping_address']['address']['company_name'] ?? ''" full />
                    <x-storefront.checkout.form-field label="Street address" name="address_line_1" placeholder="Street address" :value="$state['shipping_address']['address']['address_line_1'] ?? ''" full required />
                    <x-storefront.checkout.form-field label="Apartment / suite" name="address_line_2" placeholder="Optional" :value="$state['shipping_address']['address']['address_line_2'] ?? ''" />
                    <x-storefront.checkout.form-field label="City" name="city" placeholder="City" :value="$state['shipping_address']['address']['city'] ?? ''" required />
                    <x-storefront.checkout.form-field label="State" name="state" placeholder="State" :value="$state['shipping_address']['address']['state'] ?? ''" />
                    <x-storefront.checkout.form-field label="ZIP code" name="postal_code" placeholder="ZIP" :value="$state['shipping_address']['address']['postal_code'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Country" name="country" :value="$state['shipping_address']['address']['country'] ?? 'United States'" :options="['United States' => 'United States', 'Canada' => 'Canada']" required />
                    <x-storefront.checkout.form-field label="Phone" name="phone" type="tel" placeholder="Delivery phone" :value="$state['shipping_address']['address']['phone'] ?? $state['information']['phone'] ?? ''" />
                    <x-storefront.checkout.form-field label="Email" name="email" type="email" placeholder="Delivery email" :value="$state['shipping_address']['address']['email'] ?? $state['information']['email'] ?? ''" />
                    <x-storefront.checkout.form-field label="Delivery instruction" name="delivery_instruction" placeholder="Gate code, school office, club house, delivery contact..." :value="$state['shipping_address']['address']['delivery_instruction'] ?? ''" textarea full />
                </div>

                @auth
                    <label class="mt-5 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="save_to_account" value="1" class="mt-1">
                        Save this address to my account for future orders.
                    </label>
                @endauth
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-bold leading-6 text-brand-navy">
                Tip: For team deliveries, use the organization name exactly as it should appear on the shipping label.
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.information') }}">Back</a>
                <button class="btn btn-red" type="submit">Continue to Billing</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
