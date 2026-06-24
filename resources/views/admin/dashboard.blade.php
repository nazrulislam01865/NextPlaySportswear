<x-layouts.admin title="Dashboard">
    @php
        $dashboardCards = [
            ['Products', $stats['products'], route('admin.products.index')],
            ['Active Products', $stats['active_products'], route('admin.products.index', ['status' => 'active'])],
            ['Low Stock', $stats['low_stock_products'], route('admin.modules.show', 'inventory')],
            ['Customers', $stats['customers'], route('admin.modules.show', 'customers')],
        ];
        if ($canManageOrders) {
            $dashboardCards = array_merge([
                ['Orders', $stats['orders'], route('admin.orders.index')],
                ['Open Orders', $stats['open_orders'], route('admin.orders.index')],
                ['Payment Attention', $stats['payment_due'], route('admin.orders.index', ['payment_status' => 'failed'])],
                ['Open Returns', $stats['open_returns'], route('admin.returns.index')],
            ], $dashboardCards);
        }
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($dashboardCards as [$label, $value, $url])
            <a href="{{ $url }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft">
                <p class="text-xs font-black uppercase tracking-[.14em] text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-4xl font-black text-brand-dark">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-7 grid gap-6 {{ $canManageOrders ? 'xl:grid-cols-2' : 'xl:grid-cols-1' }}">
        @if($canManageOrders)
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black">Recent orders</h2>
                    <p class="text-sm text-slate-500">Newest customer orders and their current status.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-white">View All</a>
            </div>
            <div class="touch-scroll-x">
                <table class="admin-table min-w-[620px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                        <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="px-5 py-4"><a class="font-black text-brand-blue" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a><p class="text-xs text-slate-400">{{ $order->placed_at?->format('M d, Y') }}</p></td>
                                <td class="px-5 py-4"><p class="font-bold">{{ $order->customer_name }}</p><p class="max-w-[180px] truncate text-xs text-slate-400">{{ $order->customer_email }}</p></td>
                                <td class="px-5 py-4"><x-storefront.account.orders.status-pill :status="$order->status" /></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-black">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black">Recent products</h2>
                    <p class="text-sm text-slate-500">Newest catalog records and publication status.</p>
                </div>
                <a href="{{ route('admin.products.create') }}" class="btn btn-red">Add Product</a>
            </div>
            <div class="touch-scroll-x">
                <table class="admin-table min-w-[620px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                        <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Price</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentProducts as $product)
                            <tr>
                                <td class="px-5 py-4"><a class="font-black text-brand-blue" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a><p class="text-xs text-slate-400">{{ $product->sku }}</p></td>
                                <td class="px-5 py-4 text-slate-600">{{ $product->subcategory?->name ?? $product->category?->name ?? 'Uncategorized' }}</td>
                                <td class="px-5 py-4"><span class="admin-status-pill bg-slate-100 px-2.5 py-1 text-xs font-black">{{ ucfirst($product->status) }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-black">{{ $product->currency }} {{ number_format((float) $product->base_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No products yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if($canManageOrders)
    <section class="mt-7 rounded-3xl bg-brand-dark p-6 text-white shadow-card">
        <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Commerce operations</p>
                <h2 class="mt-2 text-2xl font-black">Orders, shipments, customer requests, returns, refunds, invoices, and private downloads now share one workflow.</h2>
                <p class="mt-3 max-w-4xl text-sm leading-6 text-slate-300">Use Orders for payment and fulfillment updates. Use Returns & Exchanges to review eligibility, approve requests, record refund progress, and issue credit notes.</p>
            </div>
            <div class="responsive-actions [&_.btn]:w-full sm:[&_.btn]:w-auto"><a class="btn btn-red" href="{{ route('admin.orders.index') }}">Manage Orders</a><a class="btn btn-white" href="{{ route('admin.returns.index') }}">Review Returns</a></div>
        </div>
    </section>
    @endif
</x-layouts.admin>
