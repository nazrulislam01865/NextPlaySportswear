<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" title="Customer Orders" subtitle="A complete order center for payments, production updates, shipments, returns, refunds, invoices, and repeat orders.">
    <x-slot:actions><a class="btn btn-red" href="{{ route('account.orders.index') }}">View Order History</a></x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Open Orders',$stats['open'],'Production and delivery'],
            ['Payment Due',$stats['payment_due'],'Orders needing payment'],
            ['Returns',$stats['returns'],'Returns and exchanges'],
            ['Downloads',$stats['downloads'],'Private order files'],
        ] as [$label,$value,$description])
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card"><p class="text-xs font-black uppercase tracking-[.14em] text-slate-400">{{ $label }}</p><p class="mt-2 text-4xl font-black text-brand-ink">{{ $value }}</p><p class="mt-1 text-sm text-slate-500">{{ $description }}</p></div>
        @endforeach
    </div>

    @if($orders->contains(fn($order) => $order->canPay()))
        <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5"><h3 class="font-black text-amber-900">Payment action required</h3><p class="mt-1 text-sm leading-6 text-amber-800">At least one order is waiting for secure payment. Production starts only after payment confirmation or invoice approval.</p></div>
    @endif

    <section class="mt-6 rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="text-xl font-black">Recent Orders</h3><p class="text-sm text-slate-500">Latest purchases and next available actions.</p></div><a href="{{ route('account.orders.index') }}" class="text-sm font-black text-brand-red">View all →</a></div>
        <div class="mt-5 grid gap-5">
            @forelse($orders as $order)<x-storefront.account.orders.order-card :order="$order" />@empty
                <div class="rounded-2xl bg-slate-50 p-8 text-center"><h4 class="font-black">No orders yet</h4><p class="mt-2 text-sm text-slate-500">Your completed checkouts will appear here.</p><a href="{{ route('products.index') }}" class="btn btn-red mt-4">Browse Products</a></div>
            @endforelse
        </div>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-3">
        <a href="{{ route('account.returns.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5"><h3 class="font-black">Returns & Exchanges</h3><p class="mt-2 text-sm leading-6 text-slate-500">Review eligibility, requests, labels, inspection, and final resolution.</p></a>
        <a href="{{ route('account.downloads.index') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5"><h3 class="font-black">Order Downloads</h3><p class="mt-2 text-sm leading-6 text-slate-500">Access private digital products and customer-owned order files.</p></a>
        <a href="{{ route('contact') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5"><h3 class="font-black">Order Support</h3><p class="mt-2 text-sm leading-6 text-slate-500">Get help with artwork, deadlines, payment, shipping, or post-purchase questions.</p></a>
    </section>
</x-storefront.account.orders.page>
