<x-layouts.admin title="Discounts & Coupons">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Create storefront promo codes with minimum subtotal, expiry dates, usage limits, and customer usage validation.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-red">+ Add Coupon</a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">{{ session('status') }}</div>
    @endif

    <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:grid-cols-[1fr_180px_auto]" method="GET">
        <input class="admin-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search coupon name or code">
        <select class="admin-input" name="active">
            <option value="">All statuses</option>
            <option value="1" @selected(($filters['active'] ?? '') === '1')>Active</option>
            <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactive</option>
        </select>
        <button class="btn btn-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Coupons table">
            <table class="admin-table min-w-[980px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Coupon</th>
                        <th class="px-5 py-4">Discount</th>
                        <th class="px-5 py-4">Minimum</th>
                        <th class="px-5 py-4">Usage</th>
                        <th class="px-5 py-4">Schedule</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-brand-ink">{{ $coupon->name }}</strong>
                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700">{{ $coupon->code }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <strong class="text-brand-ink">{{ $coupon->discountLabel() }}</strong>
                                @if($coupon->maximum_discount)
                                    <span class="block text-xs text-slate-500">Max ${{ number_format((float) $coupon->maximum_discount, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">${{ number_format((float) $coupon->minimum_subtotal, 2) }}</td>
                            <td class="px-5 py-4">
                                <strong class="text-brand-ink">{{ $coupon->redemptions_count }} used</strong>
                                <span class="block text-xs text-slate-500">Limit: {{ $coupon->usage_limit ?: 'Unlimited' }}</span>
                                <span class="block text-xs text-slate-500">Per customer: {{ $coupon->usage_limit_per_customer ?: 'Unlimited' }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600">
                                <span class="block">Start: {{ $coupon->starts_at?->format('M d, Y H:i') ?: 'Now' }}</span>
                                <span class="block">End: {{ $coupon->expires_at?->format('M d, Y H:i') ?: 'No expiry' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @php($status = $coupon->statusLabel())
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $status }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.coupons.edit', $coupon) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon? Existing order history will remain.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No coupons found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $coupons->links() }}</div>
</x-layouts.admin>
