<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="login"
        eyebrow="Customer account recovery"
        title="Reset your password"
        subtitle="Enter the email address used for your NextPlay customer account and we will send a secure reset link."
    >
        <div class="mx-auto max-w-[520px]">
            <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-navy text-white">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-black text-brand-ink">Secure account recovery</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Reset links expire automatically and can only be used once.
                        </p>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold leading-6 text-emerald-700" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="grid gap-5" data-single-submit>
                @csrf

                <x-storefront.auth.input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                    autofocus
                />

                <button type="submit" class="btn btn-red h-12 w-full rounded-2xl text-base" data-submitting-label="Sending reset link…">
                    Send Password Reset Link
                </button>
            </form>

            <div class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Remembered your password?</p>
                <a href="{{ route('login') }}" class="mt-3 btn btn-white w-full rounded-2xl sm:w-auto">
                    Back to Sign In
                </a>
            </div>
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
