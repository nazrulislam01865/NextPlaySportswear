<x-layouts.admin title="Shipping Methods" subtitle="Manage checkout shipping methods, rates, availability, and delivery estimate rules.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-semibold leading-6 text-slate-500">These methods are loaded dynamically during checkout. Rural ZIP surcharges are added on top from the Rural Surcharges section, and product-level shipping selected on the product page is carried automatically with the cart item.</p>
        <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-red">+ Add Shipping Method</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Shipping methods table">
            <table class="admin-table min-w-[1040px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Method</th>
                        <th class="px-5 py-4">Pricing</th>
                        <th class="px-5 py-4">Availability</th>
                        <th class="px-5 py-4">Estimate</th>
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
                                <strong>${{ number_format((float) $method->base_price, 2) }}</strong>
                                <span class="block text-xs font-semibold text-slate-500">+ ${{ number_format((float) $method->per_item_price, 2) }} / extra item</span>
                                @if($method->free_shipping_minimum)<span class="block text-xs font-black text-emerald-700">Free over ${{ number_format((float) $method->free_shipping_minimum, 2) }}</span>@endif
                                @if($method->is_quote_based)<span class="mt-1 inline-block rounded-full bg-blue-50 px-2 py-1 text-xs font-black text-blue-700">Quote based</span>@endif
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold leading-5 text-slate-600">
                                <span class="block">Qty: {{ $method->minimum_quantity ?: 'Any' }} – {{ $method->maximum_quantity ?: 'Any' }}</span>
                                <span class="block">Subtotal: {{ $method->minimum_subtotal ? '$'.number_format((float) $method->minimum_subtotal, 2) : 'Any' }} – {{ $method->maximum_subtotal ? '$'.number_format((float) $method->maximum_subtotal, 2) : 'Any' }}</span>
                                <span class="block">Location: {{ $method->country ?: 'Any country' }}{{ $method->state ? ' / '.$method->state : '' }}</span>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-700">
                                {{ $method->minimum_days }}–{{ $method->maximum_days }} business days
                                <span class="block text-xs font-semibold text-slate-500">{{ $method->starts_after_artwork_approval ? 'After artwork approval' : 'After order placement' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($method->is_default)<span class="admin-status-pill bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Default</span>@endif
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
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No shipping methods have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $methods->links() }}</div>
</x-layouts.admin>
