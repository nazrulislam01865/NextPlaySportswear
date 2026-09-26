<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="verify"
        eyebrow="Email verification"
        title="Check your inbox"
        subtitle="Verify your email address before using protected account features and secure checkout."
    >
        <div class="mx-auto max-w-[560px]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-brand-navy text-white">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                            <path d="m22 7-10 5L2 7"></path>
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <h2 class="text-lg font-black text-brand-ink">Verify {{ $customer?->email }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            We sent a signed verification link to this address. The link expires in
                            {{ $expiresInMinutes }} minutes and becomes invalid if your account email changes.
                        </p>
                    </div>
                </div>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    A fresh verification link has been sent. Please check your inbox and spam folder.
                </div>
            @elseif (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('verification_delivery_failed'))
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold leading-6 text-amber-800">
                    {{ session('verification_delivery_failed') }}
                </div>
            @endif

            @error('verification')
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold leading-6 text-brand-red">
                    {{ $message }}
                </div>
            @enderror

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <form method="POST" action="{{ route('verification.send') }}" data-single-submit>
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        Resend Verification Email
                    </button>
                </form>

                <a href="{{ route('home') }}" class="btn btn-outline btn-lg w-full">
                    Continue Shopping
                </a>
            </div>

            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-5">
                <h3 class="text-sm font-black text-brand-ink">Did not receive the email?</h3>
                <ul class="mt-3 grid gap-2 text-sm leading-6 text-slate-600">
                    <li>• Check spam, junk, and promotions folders.</li>
                    <li>• Confirm the email address above is correct.</li>
                    <li>• Use resend once; repeated requests are rate-limited for security.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
                @csrf
                <button type="submit" class="text-sm font-black text-brand-blue transition hover:text-brand-red">
                    Sign out and use another account
                </button>
            </form>
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
