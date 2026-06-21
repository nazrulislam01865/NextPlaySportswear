<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="Profile & Security"
        subtitle="Keep your contact details accurate, protect your password, and prepare your profile for faster quotes and checkout."
        :account="$account"
        :navigation="$navigation"
    >
        <div class="space-y-6">
            <x-storefront.account.section-panel
                title="Profile Details"
                description="This information helps NextPlay prepare quotes, artwork proofs, delivery updates, and customer support faster."
            >
                <form method="POST" action="{{ route('account.profile.update') }}" class="grid gap-5" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field
                            name="name"
                            label="Full name"
                            :value="auth()->user()->name"
                            autocomplete="name"
                            required
                        />

                        <x-storefront.account.form-field
                            name="email"
                            label="Email address"
                            type="email"
                            :value="auth()->user()->email"
                            autocomplete="email"
                            required
                            help="Changing your email may require verification later when email verification is enabled."
                        />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field
                            name="phone"
                            label="Phone number"
                            :value="auth()->user()->phone"
                            placeholder="+1 555 000 0000"
                            autocomplete="tel"
                        />

                        <x-storefront.account.form-field
                            name="company_name"
                            label="Company / organization"
                            :value="auth()->user()->company_name"
                            placeholder="School, club, business, or event name"
                            autocomplete="organization"
                        />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="preferred_sport" class="mb-2 block text-sm font-black text-slate-800">Preferred sport</label>
                            <select
                                id="preferred_sport"
                                name="preferred_sport"
                                class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10"
                            >
                                <option value="">Select sport</option>
                                @foreach ($options['sports'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('preferred_sport', auth()->user()->preferred_sport) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('preferred_sport')
                                <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <label class="flex gap-3 text-sm font-semibold leading-6 text-slate-600">
                                <input
                                    type="checkbox"
                                    name="marketing_consent"
                                    value="1"
                                    class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-blue focus:ring-brand-blue"
                                    @checked(old('marketing_consent', auth()->user()->marketing_consent))
                                >
                                <span>Send me custom sportswear offers, production tips, and bulk-order updates.</span>
                            </label>
                            @error('marketing_consent')
                                <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('account.dashboard') }}" class="btn btn-white rounded-2xl">Cancel</a>
                        <button type="submit" class="btn btn-red rounded-2xl">Save Profile</button>
                    </div>
                </form>
            </x-storefront.account.section-panel>

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <x-storefront.account.section-panel
                    title="Change Password"
                    description="Use a strong password that you do not use on other websites."
                >
                    <form method="POST" action="{{ route('account.password.update') }}" class="grid gap-5" novalidate>
                        @csrf
                        @method('PATCH')

                        <x-storefront.account.form-field
                            name="current_password"
                            label="Current password"
                            type="password"
                            autocomplete="current-password"
                            required
                        />

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-storefront.account.form-field
                                name="password"
                                label="New password"
                                type="password"
                                autocomplete="new-password"
                                required
                                help="Use at least 8 characters. Stronger rules can be enabled before production."
                            />

                            <x-storefront.account.form-field
                                name="password_confirmation"
                                label="Confirm new password"
                                type="password"
                                autocomplete="new-password"
                                required
                            />
                        </div>

                        <div class="flex justify-end border-t border-slate-200 pt-5">
                            <button type="submit" class="btn btn-red rounded-2xl">Update Password</button>
                        </div>
                    </form>
                </x-storefront.account.section-panel>

                <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card">
                    <div class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-navy text-white">
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-5"/></svg>
                    </div>
                    <h2 class="mt-5 text-xl font-black text-brand-ink">Security Notes</h2>
                    <ul class="mt-4 grid gap-3 text-sm font-semibold leading-6 text-slate-600">
                        <li class="flex gap-3"><span class="text-brand-red">•</span> Profile and password updates are auth-protected.</li>
                        <li class="flex gap-3"><span class="text-brand-red">•</span> Forms use CSRF protection and backend validation.</li>
                        <li class="flex gap-3"><span class="text-brand-red">•</span> Password updates require the current password.</li>
                        <li class="flex gap-3"><span class="text-brand-red">•</span> Customer-specific account pages are noindex.</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
