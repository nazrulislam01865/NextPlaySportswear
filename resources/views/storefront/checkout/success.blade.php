<x-layouts.storefront :seo="$seo">
    <section class="bg-slate-50 py-16">
        <div class="site-container">
            <div class="mx-auto max-w-3xl rounded-[32px] border border-slate-200 bg-white p-8 text-center shadow-card lg:p-12">
                <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-green-100 text-4xl text-green-700">✓</div>
                <p class="mt-6 text-xs font-black uppercase tracking-[.22em] text-brand-red">Order received</p>
                <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight text-brand-ink sm:text-5xl">Thank You</h1>

                @php($order = $state['placed_order'] ?? null)
                @if (is_array($order))
                    <p class="mt-4 text-base font-semibold leading-7 text-slate-600">Your secure order snapshot was created successfully.</p>
                    <div class="mt-6 rounded-2xl bg-slate-50 p-5 text-left">
                        <div class="flex items-center justify-between gap-4"><span class="font-black text-slate-500">Order number</span><strong class="text-brand-ink">{{ $order['order_number'] }}</strong></div>
                        <div class="mt-3 flex items-center justify-between gap-4"><span class="font-black text-slate-500">Status</span><strong class="text-brand-ink">{{ \Illuminate\Support\Str::headline($order['status']) }}</strong></div>
                        <div class="mt-3 flex items-center justify-between gap-4"><span class="font-black text-slate-500">Total</span><strong class="text-brand-ink">${{ number_format($order['totals']['total'] ?? 0, 2) }}</strong></div>
                    </div>
                @else
                    <p class="mt-4 text-base font-semibold leading-7 text-slate-600">No order confirmation is available in this session.</p>
                @endif

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('account.dashboard') }}" class="btn btn-red">My Account</a>
                    <a href="{{ route('products.index') }}" class="btn btn-white">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.storefront>
