<x-layouts.admin title="Artwork Approvals" subtitle="Review customer feedback, view proofs, and open the relevant order to publish the next version.">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-brand-ink">Customer Artwork Review Queue</h2>
            <p class="mt-1 text-sm text-slate-500">Latest proof for each ordered item. Open an order to review all versions, customer notes and messages.</p>
        </div>
        <a class="btn btn-white" href="{{ route('admin.orders.index') }}">Browse Orders</a>
    </div>

    <x-admin.filter-bar label="Filter artwork approvals">
        <div class="admin-filter-bar__search">
            <input class="admin-input" type="search" name="q" value="{{ request('q') }}" placeholder="Search order, customer, or design" aria-label="Search artwork approvals" maxlength="190">
        </div>
        <div class="admin-filter-bar__field">
            <select class="admin-input" name="status" aria-label="Artwork review status">
                <option value="">All statuses</option>
                <option value="pending_review" @selected(request('status') === 'pending_review')>Pending Review</option>
                <option value="needs_revision" @selected(request('status') === 'needs_revision')>Needs Revision</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
        </div>
        <div class="admin-filter-bar__actions flex items-center gap-2">
            <button type="submit" class="btn btn-red">Filter</button>
            @if(request()->filled('q') || request()->filled('status'))
                <a class="btn btn-white" href="{{ route('admin.artwork-approvals.index') }}">Clear</a>
            @endif
        </div>
    </x-admin.filter-bar>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card" aria-label="Customer artwork proofs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Order / Customer</th>
                        <th scope="col" class="px-4 py-3">Product / Design</th>
                        <th scope="col" class="px-4 py-3">Version</th>
                        <th scope="col" class="px-4 py-3">Status / Customer response</th>
                        <th scope="col" class="px-4 py-3">Last Updated</th>
                        <th scope="col" class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($proofs as $proof)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                @if($proof->order)
                                    <a class="font-semibold text-brand-ink hover:text-brand-red" href="{{ route('admin.orders.show', $proof->order) }}#order-artwork-proofs">{{ $proof->order->order_number }}</a>
                                    <p class="mt-1 text-xs text-slate-600">{{ $proof->order->customer_name }}</p>
                                    <p class="break-all text-xs text-slate-500">{{ $proof->order->customer_email }}</p>
                                @else
                                    <span class="text-slate-500">Order unavailable</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-800">{{ $proof->orderItem?->product_name ?? 'Order item unavailable' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $proof->design_name ?: 'No design name' }}</p>
                            </td>
                            <td class="px-4 py-4 font-medium text-slate-700">v{{ $proof->version }}</td>
                            <td class="px-4 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-amber-100 text-amber-800' => $proof->status === 'pending_review',
                                    'bg-rose-100 text-rose-800' => in_array($proof->status, ['needs_revision', 'rejected'], true),
                                    'bg-emerald-100 text-emerald-800' => $proof->status === 'approved',
                                ])>{{ $proof->statusLabel() }}</span>
                                @if($proof->customer_feedback)
                                    <p class="mt-2 max-w-xs break-words text-xs leading-5 text-slate-600">{{ \Illuminate\Support\Str::limit($proof->customer_feedback, 130) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ \App\Support\ViewerTime::render($proof->updated_at, 'M j, Y · g:i A') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($proof->order)
                                        <a class="btn btn-white" href="{{ route('admin.orders.artwork-proofs.file', [$proof->order, $proof]) }}" target="_blank" rel="noopener noreferrer">View Proof</a>
                                        <a class="btn btn-red" href="{{ route('admin.orders.show', $proof->order) }}#order-artwork-proofs">{{ $canManageOrders ? 'Review / Manage' : 'Review Details' }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No artwork proofs found. @if($canManageOrders) Open an order to upload and publish its first proof. @endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-5">{{ $proofs->links('pagination.nextplay') }}</div>
</x-layouts.admin>
