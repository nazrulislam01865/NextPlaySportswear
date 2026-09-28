<x-layouts.admin title="Time Zone Master Data" subtitle="Manage the time zones available when scheduling promotions.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Only active values are offered on the Create Sale Campaign page. Existing campaigns keep the time zone that was saved with them.</p>
        <a href="{{ route('admin.time-zones.create') }}" class="btn btn-red">+ Add Time Zone</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Time zone master data table">
            <table class="admin-table min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Label</th>
                        <th class="px-5 py-4">IANA identifier</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Sort</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timeZones as $timeZone)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-brand-ink">{{ $timeZone->label }}</td>
                            <td class="px-5 py-4 font-medium text-slate-600">{{ $timeZone->identifier }}</td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $timeZone->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $timeZone->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $timeZone->sort_order }}</td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.time-zones.edit', $timeZone) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.time-zones.destroy', $timeZone) }}" onsubmit="return confirm('Delete this time zone from Master Data? Existing campaigns will keep their saved value.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No time zones have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $timeZones->links('pagination.nextplay') }}</div>
</x-layouts.admin>
