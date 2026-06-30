<x-layouts.admin title="Rural Area Surcharges" subtitle="Add ZIP/postal-code based delivery surcharges for rural or remote areas.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-semibold leading-6 text-slate-500">These rules are checked during checkout after the customer selects a shipping address. When a ZIP/postal code matches, the surcharge is added to the selected shipping method and saved with the order.</p>
        <a href="{{ route('admin.rural-area-surcharges.create') }}" class="btn btn-red">+ Add Surcharge</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Rural area surcharge table">
            <table class="admin-table min-w-[920px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Rule</th>
                        <th class="px-5 py-4">Location</th>
                        <th class="px-5 py-4">ZIP / Postal Patterns</th>
                        <th class="px-5 py-4">Amount</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($surcharges as $surcharge)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-brand-ink">{{ $surcharge->name }}</strong>
                                <span class="text-xs font-semibold text-slate-500">Updated {{ $surcharge->updated_at?->format('M d, Y') }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <strong>{{ $surcharge->country ?: 'Any country' }}</strong>
                                <span class="block text-xs font-semibold text-slate-500">{{ $surcharge->state ?: 'Any state' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex max-w-md flex-wrap gap-1.5">
                                    @foreach(array_slice($surcharge->patternList(), 0, 8) as $pattern)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase text-slate-700">{{ $pattern }}</span>
                                    @endforeach
                                    @if(count($surcharge->patternList()) > 8)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-500">+{{ count($surcharge->patternList()) - 8 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 font-black text-brand-ink">${{ number_format((float) $surcharge->amount, 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $surcharge->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $surcharge->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.rural-area-surcharges.edit', $surcharge) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.rural-area-surcharges.destroy', $surcharge) }}" onsubmit="return confirm('Delete this rural area surcharge?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No rural surcharge rules have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $surcharges->links() }}</div>
</x-layouts.admin>
