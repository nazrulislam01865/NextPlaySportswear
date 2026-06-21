<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="register"
        eyebrow="Create customer account"
        title="Start faster orders"
        subtitle="Create an account for saved checkout details, team order tracking, proof approvals, and repeat custom sportswear purchases."
    >
        <div class="mx-auto max-w-[560px]">
            <div class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                    <p class="text-xl font-black text-brand-red">1</p>
                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-slate-600">Save details</p>
                </div>
                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                    <p class="text-xl font-black text-brand-red">2</p>
                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-slate-600">Track proof</p>
                </div>
                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                    <p class="text-xl font-black text-brand-red">3</p>
                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-slate-600">Repeat order</p>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="grid gap-5" novalidate>
                @csrf

                <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

                <x-storefront.auth.input
                    name="name"
                    label="Full name"
                    placeholder="Your name"
                    autocomplete="name"
                    required
                />

                <x-storefront.auth.input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-storefront.auth.input
                        name="password"
                        label="Password"
                        type="password"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        required
                    />

                    <x-storefront.auth.input
                        name="password_confirmation"
                        label="Confirm password"
                        type="password"
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                        required
                    />
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex gap-3 text-sm leading-6 text-slate-600">
                        <input
                            type="checkbox"
                            name="terms"
                            value="1"
                            class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-blue focus:ring-brand-blue"
                            @checked(old('terms'))
                            required
                        >
                        <span>
                            I agree that custom sportswear orders may require artwork proof review, production time, and custom-order return terms before final fulfillment.
                        </span>
                    </label>

                    @error('terms')
                        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
                    @enderror

                    @error('website')
                        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-red h-12 w-full rounded-2xl text-base">
                    Create Account
                </button>
            </form>

            <div class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Already have a customer account?</p>
                <a href="{{ route('login') }}" class="mt-3 btn btn-white w-full rounded-2xl sm:w-auto">
                    Sign In Instead
                </a>
            </div>
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
