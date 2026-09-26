<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.shell
        mode="verify"
        eyebrow="Email verified"
        title="You're verified"
        subtitle="Your email address has been confirmed successfully. Your customer account and protected checkout features are now unlocked."
    >
        <div class="mx-auto max-w-[560px]">
            <div class="text-center">
                <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-emerald-50 ring-8 ring-emerald-50/60">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/20" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 4 4L19 6"></path>
                        </svg>
                    </span>
                </div>

                <p class="mt-7 text-xs font-black uppercase tracking-[.2em] text-emerald-600">
                    Verification complete
                </p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink sm:text-3xl">
                    Email verified successfully
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-600 sm:text-base">
                    <span class="font-bold text-brand-ink">{{ $customer?->email }}</span>
                    is now verified. You can safely continue using your NextPlay customer account.
                </p>
            </div>

            <div class="mt-7 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-500 text-sm font-black text-white">✓</span>
                    <div>
                        <h3 class="text-sm font-black text-emerald-900">Your account is ready</h3>
                        <p class="mt-1 text-sm leading-6 text-emerald-800">
                            Account features, order history, saved details, and secure checkout are now available to you.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <a href="{{ $continueUrl }}" class="btn btn-primary btn-lg w-full">
                    {{ $continueLabel }}
                </a>

                <a href="{{ route('products.index') }}" class="btn btn-outline btn-lg w-full">
                    Continue Shopping
                </a>
            </div>

            @if ($continueLabel !== 'Go to My Account')
                <div class="mt-5 text-center">
                    <a href="{{ route('account.dashboard') }}" class="text-sm font-black text-brand-blue transition hover:text-brand-red">
                        Go to My Account
                    </a>
                </div>
            @endif
        </div>
    </x-storefront.auth.shell>
</x-layouts.storefront>
