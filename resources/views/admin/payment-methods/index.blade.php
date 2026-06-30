<x-layouts.admin title="Payment Methods" subtitle="Manage checkout payment choices, amount rules, provider behavior, and manual review settings.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-semibold leading-6 text-slate-500">These payment methods are loaded dynamically in checkout. The payment step always receives the final server-side grand total from the selected shipping method, rural surcharge, discount, and tax.</p>
        <a href="{{ route('admin.payment-methods.create') }}" class="btn btn-red">+ Add Payment Method</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Payment methods table">
            <table class="admin-table min-w-[1060px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Method</th>
                        <th class="px-5 py-4">Provider</th>
                        <th class="px-5 py-4">Amount Rules</th>
                        <th class="px-5 py-4">Behavior</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($methods as $method)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-brand-ink">{{ $method->name }}</strong>
                                <span class="text-xs font-semibold text-slate-500">{{ $method->code }}</span>
                                @if($method->description)<p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">{{ $method->description }}</p>@endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <strong>{{ str($method->provider)->headline() }}</strong>
                                <span class="block text-xs font-semibold text-slate-500">{{ str($method->payment_type)->replace('_', ' ')->headline() }}</span>
                                @if($method->badge)<span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-700">{{ $method->badge }}</span>@endif
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold leading-5 text-slate-600">
                                <span class="block">Min: {{ $method->minimum_total ? '$'.number_format((float) $method->minimum_total, 2) : 'Any' }}</span>
                                <span class="block">Max: {{ $method->maximum_total ? '$'.number_format((float) $method->maximum_total, 2) : 'Any' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_online ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_online ? 'Online' : 'Offline/manual' }}</span>
                                    @if($method->requires_provider_redirect)<span class="admin-status-pill bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">Provider redirect</span>@endif
                                    @if($method->requires_manual_review)<span class="admin-status-pill bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Manual review</span>@endif
                                    @if($method->allows_saved_methods)<span class="admin-status-pill bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Saved cards</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($method->is_default)<span class="admin-status-pill bg-brand-red/10 px-2.5 py-1 text-xs font-bold text-brand-red">Default</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.payment-methods.edit', $method) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" onsubmit="return confirm('Delete this payment method?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No payment methods have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $methods->links() }}</div>
</x-layouts.admin>
