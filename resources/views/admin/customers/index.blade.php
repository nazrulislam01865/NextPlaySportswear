<x-layouts.admin title="Customers" eyebrow="Commerce" subtitle="Browse storefront customer accounts, order activity, and account details.">
    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Total customers</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['total']) }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Active accounts</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['active']) }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Verified emails</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['verified']) }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Customers with orders</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['with_orders']) }}</p>
        </section>
    </div>

    <form method="GET" class="mb-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-card lg:grid-cols-6">
        <input class="admin-input mt-0 lg:col-span-2" name="q" value="{{ request('q') }}" placeholder="Name, email, phone, or company">

        <select class="admin-input mt-0" name="account_status">
            <option value="">All accounts</option>
            <option value="active" @selected(request('account_status') === 'active')>Active</option>
            <option value="suspended" @selected(in_array(request('account_status'), ['suspended', 'inactive'], true))>Suspended</option>
        </select>

        <select class="admin-input mt-0" name="verification">
            <option value="">All verification</option>
            <option value="verified" @selected(request('verification') === 'verified')>Verified</option>
            <option value="unverified" @selected(request('verification') === 'unverified')>Unverified</option>
        </select>

        <select class="admin-input mt-0" name="orders">
            <option value="">Any order activity</option>
            <option value="with_orders" @selected(request('orders') === 'with_orders')>Has orders</option>
            <option value="without_orders" @selected(request('orders') === 'without_orders')>No orders</option>
        </select>

        <select class="admin-input mt-0" name="marketing">
            <option value="">Any marketing status</option>
            <option value="yes" @selected(request('marketing') === 'yes')>Opted in</option>
            <option value="no" @selected(request('marketing') === 'no')>Not opted in</option>
        </select>

        <div class="flex flex-wrap gap-2 lg:col-span-6 lg:justify-between">
            <select class="admin-input mt-0 min-w-[190px]" name="sort">
                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest customers</option>
                <option value="name" @selected(request('sort') === 'name')>Name A-Z</option>
                <option value="orders" @selected(request('sort') === 'orders')>Most orders</option>
                <option value="last_order" @selected(request('sort') === 'last_order')>Most recent order</option>
            </select>
            <div class="flex gap-2">
                @if(request()->hasAny(['q', 'account_status', 'verification', 'orders', 'marketing', 'sort']))
                    <a class="btn btn-white" href="{{ route('admin.customers.index') }}">Clear</a>
                @endif
                <button class="btn btn-red" type="submit">Filter customers</button>
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-brand-ink">Customer Directory</h2>
            <p class="mt-1 text-sm text-slate-500">Storefront customer accounts are kept separate from Admin Users.</p>
        </div>

        <div class="admin-table-scroll" tabindex="0" aria-label="Customers table">
            <table class="admin-table min-w-[1180px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Company / Phone</th>
                        <th class="px-5 py-3">Account</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3 text-center">Orders</th>
                        <th class="px-5 py-3 text-center">Returns</th>
                        <th class="px-5 py-3">Last order</th>
                        <th class="px-5 py-3">Last login</th>
                        <th class="px-5 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-black text-brand-blue">{{ $customer->name }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $customer->email }}</p>
                                <p class="mt-1 text-[11px] font-semibold text-slate-400">Joined {{ $customer->created_at?->format('M d, Y') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-700">{{ $customer->company_name ?: 'No company' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $customer->phone ?: 'No phone' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $customer->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                                    {{ $customer->is_active ? 'Active' : 'Suspended' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $customer->email_verified_at ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                                    {{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                                <p class="mt-2 text-[11px] font-semibold text-slate-400">{{ $customer->marketing_consent ? 'Marketing opt-in' : 'No marketing opt-in' }}</p>
                            </td>
                            <td class="px-5 py-4 text-center font-black text-brand-ink">{{ number_format($customer->orders_count) }}</td>
                            <td class="px-5 py-4 text-center font-black text-brand-ink">{{ number_format($customer->order_return_requests_count) }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $customer->last_order_at ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('M d, Y') : 'Never' }}
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $customer->last_login_at?->format('M d, Y') ?: 'Never' }}</td>
                            <td class="px-5 py-4 text-right">
                                <a class="btn btn-white" href="{{ route('admin.customers.show', $customer) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-14 text-center text-slate-500">No customers match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $customers->links('pagination.nextplay') }}</div>
</x-layouts.admin>
