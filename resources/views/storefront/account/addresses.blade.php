<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="Saved Addresses"
        subtitle="Manage delivery and billing addresses for faster checkout, repeat orders, and quote requests."
        :account="$account"
        :navigation="$navigation"
    >
        <div class="space-y-6">
            <div class="grid gap-5 md:grid-cols-3">
                <x-storefront.account.stat-card label="Saved Addresses" :value="$addressBook['total']" description="Available at checkout" />
                <x-storefront.account.stat-card label="Default Address" :value="$addressBook['default'] ? 'Set' : 'Not Set'" description="Used first for checkout" />
                <x-storefront.account.stat-card label="Protection" value="Private" description="Visible only to your account" />
            </div>

            <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Address book</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-ink">Already saved addresses</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-600">
                            These addresses can be reused for team shipments, quote requests, repeat orders, and checkout.
                        </p>
                    </div>
                    <a href="#add-address" class="btn btn-red rounded-2xl">Add New Address</a>
                </div>

                @if ($addressBook['addresses']->isNotEmpty())
                    <div class="mt-6 grid gap-5 xl:grid-cols-2">
                        @foreach ($addressBook['addresses'] as $address)
                            <x-storefront.account.address-card :address="$address" />
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[26px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-white text-brand-red shadow-card">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-black text-brand-ink">No saved address yet</h3>
                        <p class="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-slate-600">
                            Add a shipping or billing address now so future orders need fewer steps.
                        </p>
                    </div>
                @endif
            </section>

            <section id="add-address" class="rounded-[30px] border border-slate-200 bg-white shadow-card">
                <div class="rounded-t-[30px] border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white p-5 md:p-7">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Add address</p>
                            <h2 class="mt-2 text-2xl font-black text-brand-ink">Save a new address</h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                                Use accurate delivery details to reduce checkout errors and production delays.
                            </p>
                        </div>
                        <span class="rounded-full bg-brand-navy px-4 py-2 text-xs font-black uppercase tracking-wide text-white">Secure form</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('account.addresses.store') }}" class="grid gap-5 p-5 md:p-7" novalidate>
                    @csrf

                    <div>
                        <label for="type" class="mb-2 block text-sm font-black text-slate-800">Address Type <span class="text-brand-red">*</span></label>
                        <select id="type" name="type" required class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10">
                            <option value="">-- Select address type --</option>
                            @foreach ($addressBook['types'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field name="first_name" label="First Name" :value="old('first_name')" placeholder="Enter first name" autocomplete="given-name" required />
                        <x-storefront.account.form-field name="last_name" label="Last Name" :value="old('last_name')" placeholder="Enter last name" autocomplete="family-name" required />
                    </div>

                    <x-storefront.account.form-field name="company_name" label="Company Name" :value="old('company_name')" placeholder="Enter company name" autocomplete="organization" />
                    <x-storefront.account.form-field name="address_line_1" label="Address Line 1" :value="old('address_line_1')" placeholder="Enter street address" autocomplete="address-line1" required />
                    <x-storefront.account.form-field name="address_line_2" label="Address Line 2" :value="old('address_line_2')" placeholder="Enter apartment, suite, or unit (optional)" autocomplete="address-line2" />

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field name="city" label="City" :value="old('city')" placeholder="Enter city or town" autocomplete="address-level2" required />
                        <div>
                            <label for="state" class="mb-2 block text-sm font-black text-slate-800">State</label>
                            <select id="state" name="state" class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10" autocomplete="address-level1">
                                <option value="">-- Select --</option>
                                @foreach ($states as $code => $label)
                                    <option value="{{ $code }}" @selected(old('state') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('state')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="country" class="mb-2 block text-sm font-black text-slate-800">Country <span class="text-brand-red">*</span></label>
                            <select id="country" name="country" required class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10" autocomplete="country-name">
                                <option value="United States" @selected(old('country', 'United States') === 'United States')>United States</option>
                                <option value="Canada" @selected(old('country') === 'Canada')>Canada</option>
                                <option value="United Kingdom" @selected(old('country') === 'United Kingdom')>United Kingdom</option>
                                <option value="Australia" @selected(old('country') === 'Australia')>Australia</option>
                            </select>
                            @error('country')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                        </div>
                        <x-storefront.account.form-field name="postal_code" label="Zip Code" :value="old('postal_code')" placeholder="Enter zip or postal code" autocomplete="postal-code" required />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field name="phone" label="Phone Number" :value="old('phone')" placeholder="(XXX)-XXX-XXXX" autocomplete="tel" />
                        <x-storefront.account.form-field name="email" label="Email Address" type="email" :value="old('email', auth()->user()->email)" placeholder="Enter email address" autocomplete="email" />
                    </div>

                    <label class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold leading-6 text-slate-600">
                        <input type="checkbox" name="is_default" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-blue focus:ring-brand-blue" @checked(old('is_default'))>
                        <span>Make this my default address for this address type.</span>
                    </label>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('account.dashboard') }}" class="btn btn-white rounded-2xl">Cancel</a>
                        <button type="submit" class="btn btn-red rounded-2xl">Save Address</button>
                    </div>
                </form>
            </section>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
