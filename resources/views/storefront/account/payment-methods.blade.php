<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="Saved Payment Methods"
        subtitle="Manage tokenized payment references for faster checkout without storing raw card details."
        :account="$account"
        :navigation="$navigation"
    >
        <div class="space-y-6">
            <div class="grid gap-5 md:grid-cols-3">
                <x-storefront.account.stat-card label="Saved Methods" :value="$wallet['total']" description="Available for checkout" />
                <x-storefront.account.stat-card label="Default Method" :value="$wallet['default'] ? 'Set' : 'Not Set'" description="Used first at checkout" />
                <x-storefront.account.stat-card label="Card Data" value="Protected" description="Only brand, last 4, and expiry are stored" />
            </div>

            <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Payment wallet</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-ink">Already saved payment methods</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-600">
                            These methods are shown as safe card references. In production, Stripe or PayPal should create the token before saving.
                        </p>
                    </div>
                    <a href="#add-payment-method" class="btn btn-red rounded-2xl">Add Payment Method</a>
                </div>

                @if ($wallet['paymentMethods']->isNotEmpty())
                    <div class="mt-6 grid gap-5 xl:grid-cols-2">
                        @foreach ($wallet['paymentMethods'] as $paymentMethod)
                            <x-storefront.account.payment-method-card :payment-method="$paymentMethod" />
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[26px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-white text-brand-red shadow-card">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-black text-brand-ink">No saved payment method yet</h3>
                        <p class="mx-auto mt-2 max-w-xl text-sm font-semibold leading-6 text-slate-600">
                            Add a payment method reference for faster checkout. Raw card numbers and CVV are never stored.
                        </p>
                    </div>
                @endif
            </section>

            <section id="add-payment-method" class="overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-card">
                <div class="rounded-t-[30px] border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white p-5 md:p-7">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Add card</p>
                            <h2 class="mt-2 text-2xl font-black text-brand-ink">Add Credit Card</h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                                The form validates card details for user experience. Only a tokenized reference, card brand, last four digits, and expiration are saved.
                            </p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-black uppercase tracking-wide text-emerald-700">No raw card storage</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('account.payment-methods.store') }}" class="grid gap-5 p-5 md:p-7" novalidate>
                    @csrf

                    <div>
                        <label for="card_number" class="mb-2 block text-sm font-black text-slate-800">Card Number <span class="text-brand-red">*</span></label>
                        <input id="card_number" name="card_number" value="" inputmode="numeric" autocomplete="cc-number" placeholder="•••• •••• •••• ••••" class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-4 text-lg font-black tracking-wide text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10" required>
                        @error('card_number')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-800">Expiration Date <span class="text-slate-400">(MM/YYYY)</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <select name="expiry_month" autocomplete="cc-exp-month" required class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10">
                                    <option value="">MM</option>
                                    @for ($month = 1; $month <= 12; $month++)
                                        <option value="{{ $month }}" @selected((int) old('expiry_month') === $month)>{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endfor
                                </select>
                                <select name="expiry_year" autocomplete="cc-exp-year" required class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10">
                                    <option value="">YYYY</option>
                                    @foreach ($wallet['expiryYears'] as $year)
                                        <option value="{{ $year }}" @selected((int) old('expiry_year') === $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('expiry_month')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                            @error('expiry_year')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="cvv" class="mb-2 block text-sm font-black text-slate-800">CVV <span class="text-slate-400">(3–4 digits)</span></label>
                            <input id="cvv" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="•••" class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-4 text-lg font-black tracking-wide text-slate-800 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10" required>
                            <p class="mt-2 text-xs font-bold text-slate-500">CVV is validated for submission but never saved.</p>
                            @error('cvv')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-storefront.account.form-field name="billing_name" label="Name on Card" :value="old('billing_name', auth()->user()->name)" placeholder="Cardholder name" autocomplete="cc-name" />
                        <x-storefront.account.form-field name="nickname" label="Card Nickname" :value="old('nickname')" placeholder="e.g. Personal Visa, Office Card" />
                    </div>

                    <div class="grid gap-4 rounded-3xl border border-emerald-200 bg-emerald-50 p-5 md:grid-cols-[auto_1fr] md:items-start">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-600 text-white">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-5"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-emerald-900">Payment security rule</h3>
                            <p class="mt-1 text-sm font-semibold leading-6 text-emerald-800">
                                For production, use Stripe/PayPal vaulting. This page stores only safe display metadata and a provider reference. It does not store full card numbers or CVV.
                            </p>
                            <label class="mt-4 flex gap-3 text-sm font-bold leading-6 text-emerald-900">
                                <input type="checkbox" name="card_consent" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-600" @checked(old('card_consent')) required>
                                <span>I understand raw card number and CVV must not be stored.</span>
                            </label>
                            @error('card_consent')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold leading-6 text-slate-600">
                        <input type="checkbox" name="is_default" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-blue focus:ring-brand-blue" @checked(old('is_default'))>
                        <span>Make this my default payment method.</span>
                    </label>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('account.dashboard') }}" class="btn btn-white rounded-2xl">Cancel</a>
                        <button type="submit" class="btn btn-red rounded-2xl">Save Card</button>
                    </div>
                </form>
            </section>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
