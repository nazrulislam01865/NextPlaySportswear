<x-layouts.admin title="Shipping Method Master Data" subtitle="Create reusable shipping methods and assign them from the product add/edit page.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Manage the shipping options that products can use. Each method has one clean extra-charge rule, a delivery estimate, and status controls.</p>
        <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-red">+ Add Shipping Method</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Shipping methods table">
            <table class="admin-table min-w-[880px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Method</th>
                        <th class="px-5 py-4">Extra charge</th>
                        <th class="px-5 py-4">Estimate</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($methods as $method)
                        @php
                            $chargeAmount = $method->effectiveChargeAmount();
                            $chargeText = $chargeAmount > 0 ? '$'.number_format($chargeAmount, 2) : 'Included';
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block font-semibold text-brand-ink">{{ $method->name }}</strong>
                                <span class="text-xs font-medium text-slate-500">{{ $method->code }}</span>
                                @if($method->description)<p class="mt-1 max-w-sm text-xs font-normal leading-5 text-slate-500">{{ $method->description }}</p>@endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <strong>{{ $chargeText }}</strong>
                                <span class="block text-xs font-medium text-slate-500">{{ $method->chargeApplicationLabel() }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $method->minimum_days }}–{{ $method->maximum_days }} business days
                                <span class="block text-xs font-medium text-slate-500">{{ $method->starts_after_artwork_approval ? 'After artwork approval' : 'After order placement' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($method->is_default)<span class="admin-status-pill bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Default</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.shipping-methods.edit', $method) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.shipping-methods.destroy', $method) }}" onsubmit="return confirm('Delete this shipping method?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No shipping methods have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $methods->links() }}</div>
</x-layouts.admin>
