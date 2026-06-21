<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="login"
        eyebrow="Secure customer login"
        title="Welcome back"
        subtitle="Sign in to manage your custom jerseys, quote requests, artwork proofs, and order updates."
    >
        <div class="mx-auto max-w-[520px]">
            <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-navy text-white">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
                            <path d="m9 12 2 2 4-5"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-black text-brand-ink">Secure sign in</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Your prices, order status, and proof approvals are always handled by the Laravel backend.
                        </p>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="grid gap-5" novalidate>
                @csrf

                <x-storefront.auth.input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                />

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="password" class="block text-sm font-black text-slate-800">
                            Password <span class="text-brand-red">*</span>
                        </label>

                        <a href="{{ route('password.request') }}" class="text-sm font-black text-brand-blue hover:text-brand-red">
                            Forgot password?
                        </a>
                    </div>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                        class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10"
                    >

                    @error('password')
                        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="inline-flex items-center gap-3 text-sm font-bold text-slate-600">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue"
                            @checked(old('remember'))
                        >
                        Keep me signed in
                    </label>

                    <a href="{{ route('checkout.index') }}" class="text-sm font-black text-slate-700 hover:text-brand-red">
                        Checkout as guest
                    </a>
                </div>

                <button type="submit" class="btn btn-red h-12 w-full rounded-2xl text-base">
                    Sign In Securely
                </button>
            </form>

            <div class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center">
                <p class="text-sm font-bold text-slate-600">New to NextPlay Sportswear?</p>
                <a href="{{ route('register') }}" class="mt-3 btn btn-white w-full rounded-2xl sm:w-auto">
                    Create Customer Account
                </a>
            </div>
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
