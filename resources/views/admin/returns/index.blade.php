<x-layouts.admin title="Returns & Exchanges">
    <x-admin.filter-bar label="Returns and exchanges filters">
        <div class="admin-filter-bar__field admin-filter-bar__field--md">
            <x-admin.searchable-select id="returns-filter-status" name="status" label="Return status">
                <option value="">All statuses</option>
                @foreach($returnStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </x-admin.searchable-select>
        </div>
        <div class="admin-filter-bar__field admin-filter-bar__field--md">
            <x-admin.searchable-select id="returns-filter-type" name="type" label="Return type">
                <option value="">Returns and exchanges</option>
                <option value="return" @selected(request('type') === 'return')>Returns</option>
                <option value="exchange" @selected(request('type') === 'exchange')>Exchanges</option>
            </x-admin.searchable-select>
        </div>
        <div class="admin-filter-bar__actions">
            <button class="btn btn-red" type="submit">Filter</button>
            @if(request()->hasAny(['status', 'type']))
                <a class="btn btn-white" href="{{ route('admin.returns.index') }}">Clear</a>
            @endif
        </div>
    </x-admin.filter-bar>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card"><div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center"><div><h2 class="text-xl font-black">Post-Purchase Requests</h2><p class="text-sm text-slate-500">Eligibility, approval, receipt, refund, credit note, and exchange handling.</p></div><a class="btn btn-white" href="{{ route('admin.orders.index') }}">Orders</a></div><div class="touch-scroll-x" tabindex="0" aria-label="Returns and exchanges table"><table class="admin-table min-w-[900px] text-sm"><thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500"><tr><th class="px-5 py-3">Request</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Order</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Requested</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($returns as $return)<tr><td class="px-5 py-4"><a class="font-black text-brand-blue" href="{{ route('admin.returns.show',$return) }}">{{ $return->return_number }}</a></td><td class="px-5 py-4"><p class="font-black">{{ $return->user->name }}</p><p class="text-xs text-slate-500">{{ $return->user->email }}</p></td><td class="px-5 py-4"><a class="font-bold text-brand-blue" href="{{ route('admin.orders.show',$return->order) }}">{{ $return->order->order_number }}</a></td><td class="px-5 py-4 font-black">{{ ucfirst($return->type) }}</td><td class="px-5 py-4"><x-storefront.account.orders.status-pill :status="$return->status" /></td><td class="px-5 py-4">{{ \App\Support\ViewerTime::render($return->requested_at, 'M d, Y') }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No requests found.</td></tr>@endforelse</tbody></table></div></section><div class="mt-6">{{ $returns->links('pagination.nextplay') }}</div>
</x-layouts.admin>
