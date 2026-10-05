@php
    $canManageBulkQuoteMasterData = auth('admin')->user()?->canAdmin('orders.manage') ?? false;
@endphp
<x-layouts.admin title="Bulk Quote Budget Range Master Data" subtitle="Manage the budget options shown on the Bulk Quote form.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Active values appear in the Budget Range dropdown. Existing quote records keep their saved internal value even if a master-data item is later removed.</p>
        @if($canManageBulkQuoteMasterData)
            <a href="{{ route('admin.bulk-quote-budget-ranges.create') }}" class="btn btn-red">+ Add Budget Range</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Bulk quote budget range master data table">
            <table class="admin-table min-w-[720px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Label</th>
                        <th class="px-5 py-4">Internal Value</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Sort</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($budgetRanges as $range)
                        <tr>
                            <td class="px-5 py-4"><strong class="font-semibold text-brand-ink">{{ $range->label }}</strong></td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-500">{{ $range->value }}</td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $range->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $range->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $range->sort_order }}</td>
                            <td class="px-5 py-4">
                                @if($canManageBulkQuoteMasterData)
                                    <div class="admin-row-actions">
                                        <a class="admin-row-action border-slate-200" href="{{ route('admin.bulk-quote-budget-ranges.edit', $range) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.bulk-quote-budget-ranges.destroy', $range) }}" onsubmit="return confirm('Delete this budget range? Existing saved quotes will keep their saved value.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-sm text-slate-400">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No budget ranges have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $budgetRanges->links('pagination.nextplay') }}</div>
</x-layouts.admin>
