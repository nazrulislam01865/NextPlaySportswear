<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" title="My Orders" subtitle="Search, filter, and manage every order while keeping payment, production, shipment, and return statuses clear." eyebrow="Order history">
    <x-slot:actions><a class="btn btn-white" href="{{ route('account.orders.dashboard') }}">Order Center</a></x-slot:actions>

    <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-card md:grid-cols-[1fr_220px_220px_auto]">
        <input class="admin-input mt-0" name="q" value="{{ request('q') }}" placeholder="Search order number or product">
        <select class="admin-input mt-0" name="status"><option value="">All order statuses</option>@foreach($orderStatuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
        <select class="admin-input mt-0" name="payment_status"><option value="">All payment statuses</option>@foreach($paymentStatuses as $key=>$label)<option value="{{ $key }}" @selected(request('payment_status')===$key)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-navy" type="submit">Filter</button>
    </form>

    <div class="grid gap-5">
        @forelse($orders as $order)<x-storefront.account.orders.order-card :order="$order" />@empty<div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-card"><h3 class="text-xl font-black">No matching orders</h3><p class="mt-2 text-sm text-slate-500">Try clearing filters or browse products to start a new order.</p></div>@endforelse
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-storefront.account.orders.page>
