<x-layouts.admin title="Orders">
    <x-admin.filter-bar label="Order filters">
        <div class="admin-filter-bar__search">
            <input class="admin-input" name="q" value="{{ request('q') }}" placeholder="Order number, customer, or email" aria-label="Search orders">
        </div>

        <div class="admin-filter-bar__field">
            <select class="admin-input" name="status" aria-label="Order status">
                <option value="">All statuses</option>
                @foreach($orderStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__field">
            <select class="admin-input" name="payment_status" aria-label="Payment status">
                <option value="">All payments</option>
                @foreach($paymentStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if($hasFlowTrackSync)
            <div class="admin-filter-bar__field">
                <select class="admin-input" name="flowtrack_sync_status" aria-label="FlowTrack sync status">
                    <option value="">All FlowTrack</option>
                    @foreach($flowTrackSyncStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('flowtrack_sync_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="flowtrack_sync_status" value="">
        @endif

        <div class="admin-filter-bar__actions">
            <button class="btn btn-red" type="submit">Filter</button>
            @if(request()->hasAny(['q', 'status', 'payment_status', 'flowtrack_sync_status']))
                <a class="btn btn-white" href="{{ route('admin.orders.index') }}">Clear</a>
            @endif
        </div>
    </x-admin.filter-bar>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-black">Customer Orders</h2>
                <p class="text-sm text-slate-500">Payment, production, fulfillment, returns, and FlowTrack delivery state.</p>
            </div>
            <a class="btn btn-white" href="{{ route('admin.returns.index') }}">Returns & Exchanges</a>
        </div>

        <div class="touch-scroll-x" tabindex="0" aria-label="Customer orders table">
            <table class="admin-table min-w-[1040px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Payment</th>
                        <th class="px-5 py-3">Fulfillment</th>
                        @if($hasFlowTrackSync)<th class="px-5 py-3">FlowTrack</th>@endif
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a class="font-black text-brand-blue" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                <p class="text-xs text-slate-400">{{ $order->placed_at?->format('M d, Y · g:i A') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-black">{{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->customer_email }}</p>
                            </td>
                            <td class="px-5 py-4"><x-admin.status-pill :status="$order->status" /></td>
                            <td class="px-5 py-4"><x-admin.status-pill :status="$order->payment_status" /></td>
                            <td class="px-5 py-4">{{ $order->fulfillmentStatusLabel() }}</td>
                            @if($hasFlowTrackSync)
                                <td class="px-5 py-4">
                                    <x-admin.status-pill :status="$order->flowtrack_sync_status ?: 'pending'" />
                                    @if($order->flowtrack_order_number)
                                        <p class="mt-1 text-xs text-slate-400">{{ $order->flowtrack_order_number }}</p>
                                    @endif
                                </td>
                            @endif
                            <td class="px-5 py-4 text-right font-black">${{ number_format((float) $order->grand_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasFlowTrackSync ? 7 : 6 }}" class="px-5 py-12 text-center text-slate-500">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $orders->links('pagination.nextplay') }}</div>
</x-layouts.admin>
