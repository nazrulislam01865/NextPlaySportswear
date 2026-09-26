<x-layouts.storefront :seo="$seo">
    <section class="bg-slate-50 py-14 lg:py-20">
        <div class="site-container">
            <div class="mx-auto max-w-2xl border border-slate-200 bg-white p-7 shadow-card sm:p-9">
                <span class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Secure payment</span>
                <h1 class="mt-3 font-display text-3xl font-bold uppercase tracking-tight text-brand-ink sm:text-4xl">Payment verification in progress</h1>
                <p class="mt-4 text-base font-semibold leading-7 text-slate-600">{{ $message }}</p>

                <div class="mt-6 border border-blue-100 bg-blue-50 p-4 text-sm font-semibold leading-6 text-slate-700">
                    Returning from {{ $provider }} does not by itself mark an order as paid. NextPlay waits for the signed provider webhook before changing the payment status.
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('account.orders.index') }}" class="btn btn-primary">View My Orders</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign In to View Orders</a>
                    @endauth
                    <a href="{{ route('home') }}" class="btn btn-outline">Return Home</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.storefront>
