<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="login"
        eyebrow="Secure password reset"
        title="Choose a new password"
        subtitle="Use a strong password that you do not use on another website."
    >
        <div class="mx-auto max-w-[520px]">
            <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-bold leading-6 text-slate-600">
                    Your new password must contain at least eight characters, including letters and numbers.
                </p>
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
                />

                <x-storefront.auth.input
                    name="password"
                    label="New password"
                    type="password"
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    required
                    autofocus
                />

                <x-storefront.auth.input
                    name="password_confirmation"
                    label="Confirm new password"
                    type="password"
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
