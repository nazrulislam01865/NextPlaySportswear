<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="login"
        eyebrow="Secure password reset"
        title="Choose a new password"
        subtitle="Use a strong password that you do not use on another website."
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
                        <h2 class="text-lg font-black text-brand-ink">Create your new password</h2>
                        <p class="mt-1 text-sm font-bold leading-6 text-slate-600">
                            Use at least 8 characters with letters and numbers. This reset link expires after {{ $expiresInMinutes }} minutes and can only be used once.
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="grid gap-5" data-single-submit>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-storefront.auth.input
                    name="email"
                    label="Email address"
                    type="email"
                    :value="$email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                    readonly
                />

                <x-storefront.auth.password-input
                    name="password"
                    label="New password"
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    required
                    autofocus
                />

                <x-storefront.auth.password-input
                    name="password_confirmation"
                    label="Confirm new password"
                    placeholder="Re-enter your new password"
                    autocomplete="new-password"
                    required
                />

                <button type="submit" class="btn btn-red h-12 w-full rounded-2xl text-base" data-submitting-label="Resetting password…">
                    Reset Password
                </button>
            </form>

            <div class="mt-7 text-center">
                <a href="{{ route('password.request') }}" class="text-sm font-black text-slate-700 transition hover:text-brand-red focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-blue/15">
                    Request a new reset link
                </a>
            </div>
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
