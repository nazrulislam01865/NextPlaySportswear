<x-layouts.admin title="Dashboard">
    @php
        $dashboardCards = [];
        if ($canViewOrders) {
            $dashboardCards[] = ['Orders', $stats['orders'], route('admin.orders.index')];
            $dashboardCards[] = ['Open Orders', $stats['open_orders'], route('admin.orders.index')];
            $dashboardCards[] = ['Payment Attention', $stats['payment_due'], route('admin.orders.index', ['payment_status' => 'failed'])];
        }
        if ($canViewReturns) {
            $dashboardCards[] = ['Open Returns', $stats['open_returns'], route('admin.returns.index')];
        }
        if ($canViewProducts) {
            $dashboardCards[] = ['Products', $stats['products'], route('admin.products.index')];
            $dashboardCards[] = ['Active Products', $stats['active_products'], route('admin.products.index', ['status' => 'active'])];
        }
        if ($canViewInventory) {
            $dashboardCards[] = ['Low Stock', $stats['low_stock_products'], route('admin.modules.show', 'inventory')];
        }
        if ($canViewCustomers) {
            $dashboardCards[] = ['Customers', $stats['customers'], route('admin.modules.show', 'customers')];
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

    <div class="mt-7 space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black">Recent notifications</h2>
                    <p class="text-sm text-slate-500">Latest admin activity alerts across products, orders, master data, and settings.</p>
                </div>
                <div class="responsive-actions [&_.btn]:w-full sm:[&_.btn]:w-auto">
                    @if(($stats['unread_notifications'] ?? 0) > 0)
                        <span class="inline-flex min-h-10 items-center rounded-xl bg-red-50 px-4 text-sm font-black text-brand-red">{{ number_format($stats['unread_notifications']) }} unread</span>
                    @endif
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-white">View All</a>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentNotifications as $notification)
                    @php($data = is_array($notification->data) ? $notification->data : [])
                    <article class="grid gap-4 p-5 lg:grid-cols-[auto_minmax(220px,1fr)_auto] lg:items-center {{ $notification->read_at ? '' : 'bg-slate-50/70' }}">
                        <div class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-100 text-lg font-black text-brand-dark">
                            {{ $data['icon'] ?? '🔔' }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-black text-brand-dark">{{ $data['title'] ?? 'NextPlay Notification' }}</h3>
                                @unless($notification->read_at)
                                    <span class="rounded-full bg-brand-red px-2.5 py-1 text-[10px] font-black uppercase tracking-[.12em] text-white">New</span>
                                @endunless
                            </div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $data['message'] ?? 'Admin activity notification.' }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-400">{{ optional($notification->created_at)->format('M d, Y · g:i A') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            @if(!empty($data['url']))
                                <a class="btn btn-light" href="{{ $data['url'] }}">Open</a>
                            @endif
                            <a class="btn btn-white" href="{{ route('admin.notifications.index') }}">Details</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10 text-center text-slate-500">No notifications yet.</div>
                @endforelse
            </div>

            @if(method_exists($recentNotifications, 'hasPages') && $recentNotifications->hasPages())
                <div class="admin-pagination border-t border-slate-100 p-5">{{ $recentNotifications->links() }}</div>
            @endif
        </section>

        @if($canViewOrders)
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black">Recent orders</h2>
                    <p class="text-sm text-slate-500">Newest customer orders and their current status.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-white">View All</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentOrders as $order)
                    <article class="grid gap-4 p-5 lg:grid-cols-[1fr_1fr_auto] lg:items-center">
                        <div class="min-w-0">
                            <a class="font-black text-brand-blue" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                            <p class="mt-1 text-xs font-semibold text-slate-400">{{ $order->placed_at?->format('M d, Y · g:i A') }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-brand-dark">{{ $order->customer_name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-400">{{ $order->customer_email }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 lg:justify-end">
                            <div>
                                <p class="mb-1 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Status</p>
                                <x-storefront.account.orders.status-pill :status="$order->status" />
                            </div>
                            <div class="lg:text-right">
                                <p class="mb-1 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Total</p>
                                <p class="font-black text-brand-dark">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10 text-center text-slate-500">No orders yet.</div>
                @endforelse
            </div>

            @if(method_exists($recentOrders, 'hasPages') && $recentOrders->hasPages())
                <div class="admin-pagination border-t border-slate-100 p-5">{{ $recentOrders->links() }}</div>
            @endif
        </section>
        @endif

        @if($canViewProducts)
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black">Recent products</h2>
                    <p class="text-sm text-slate-500">Newest catalog records and publication status.</p>
                </div>
                <div class="responsive-actions [&_.btn]:w-full sm:[&_.btn]:w-auto">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-white">View All</a>
                    @if($canManageProducts)
                        <a href="{{ route('admin.products.create') }}" class="btn btn-red">Add Product</a>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentProducts as $product)
                    <article class="grid gap-4 p-5 lg:grid-cols-[1fr_1fr_auto] lg:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ $product->primaryImageUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover">
                            <div class="min-w-0">
                                <a class="block truncate font-black text-brand-blue" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                                <p class="mt-1 truncate text-xs text-slate-400">{{ $product->sku ?: 'No SKU' }}</p>
                            </div>
                        </div>
                        <div class="min-w-0 text-sm text-slate-600">
                            <p class="font-bold text-brand-dark">{{ $product->subcategory?->name ?? $product->category?->name ?? 'Uncategorized' }}</p>
                            <p class="mt-1 text-xs text-slate-400">Category</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 lg:justify-end">
                            <div>
                                <p class="mb-1 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Status</p>
                                <span class="admin-status-pill bg-slate-100 px-2.5 py-1 text-xs font-black">{{ ucfirst($product->status) }}</span>
                            </div>
                            <div class="lg:text-right">
                                <p class="mb-1 text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Price</p>
                                <p class="font-black text-brand-dark">{{ $product->currency }} {{ number_format((float) $product->base_price, 2) }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10 text-center text-slate-500">No products yet.</div>
                @endforelse
            </div>

            @if(method_exists($recentProducts, 'hasPages') && $recentProducts->hasPages())
                <div class="admin-pagination border-t border-slate-100 p-5">{{ $recentProducts->links() }}</div>
            @endif
        </section>
        @endif
    </div>

    @if($canManageOrders || $canViewReturns)
    <section class="mt-7 rounded-3xl bg-brand-dark p-6 text-white shadow-card">
        <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Commerce operations</p>
                <h2 class="mt-2 text-2xl font-black">Orders, shipments, customer requests, returns, refunds, invoices, and private downloads now share one workflow.</h2>
                <p class="mt-3 max-w-4xl text-sm leading-6 text-slate-300">Use Orders for payment and fulfillment updates. Use Returns & Exchanges to review eligibility, approve requests, record refund progress, and issue credit notes.</p>
            </div>
            <div class="responsive-actions [&_.btn]:w-full sm:[&_.btn]:w-auto">
                @if($canViewOrders)<a class="btn btn-red" href="{{ route('admin.orders.index') }}">Manage Orders</a>@endif
                @if($canViewReturns)<a class="btn btn-white" href="{{ route('admin.returns.index') }}">Review Returns</a>@endif
            </div>
        </div>
    </section>
    @endif
</x-layouts.admin>
