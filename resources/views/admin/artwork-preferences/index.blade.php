<x-layouts.admin :title="$heading.' Master Data'" subtitle="Manage reusable artwork preferences that customers can choose when requesting design help.">
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-600">{{ $type === 'pantone' ? 'Define Pantone reference colors with on-screen swatches for product configuration.' : 'Manage the design styles offered for individual products.' }}</p>
            @if(auth('admin')->user()?->canAdmin('customization.manage'))
                <a href="{{ route('admin.artwork-preferences.create', $type) }}" class="btn btn-red">+ Add {{ $type === 'pantone' ? 'Pantone Color' : 'Design Style' }}</a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.artwork-preferences.index', $type) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <label class="admin-label min-w-[220px] flex-1">Search by name or code
                    <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search {{ strtolower($heading) }}..." class="admin-input">
                </label>
                <label class="admin-label w-full sm:w-48">Status
                    <select name="status" class="admin-input">
                        <option value="">All statuses</option>
                        <option value="active" @selected($statusFilter === 'active')>Active</option>
                        <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                    </select>
                </label>
                <button class="btn btn-white" type="submit">Filter</button>
                @if($search !== '' || in_array($statusFilter, ['active', 'inactive'], true))
                    <a class="btn btn-white" href="{{ route('admin.artwork-preferences.index', $type) }}">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="admin-table-scroll" tabindex="0" aria-label="{{ $heading }} table">
                <table class="admin-table min-w-[720px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Name</th>
                            <th class="px-5 py-4">{{ $type === 'pantone' ? 'Pantone Code' : 'Style Code' }}</th>
                            @if($type === 'pantone')<th class="px-5 py-4">Color Preview</th>@endif
                            <th class="px-5 py-4">Order</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($preferences as $preference)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-semibold text-brand-ink">{{ $preference->name }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600">{{ $preference->code }}</td>
                                @if($type === 'pantone')
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="h-9 w-9 shrink-0 rounded-lg border border-slate-300 shadow-sm" style="background-color: {{ $preference->hex_color }}" role="img" aria-label="Color preview {{ $preference->hex_color }}"></span>
                                            <span class="font-mono text-xs text-slate-600">{{ $preference->hex_color }}</span>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-5 py-4 text-slate-600">{{ $preference->sort_order }}</td>
                                <td class="px-5 py-4">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $preference->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $preference->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="admin-row-actions justify-end">
                                        @if(auth('admin')->user()?->canAdmin('customization.manage'))
                                            <a class="admin-row-action border-slate-200" href="{{ route('admin.artwork-preferences.edit', [$type, $preference]) }}">Edit</a>
                                            <form method="POST" action="{{ route('admin.artwork-preferences.destroy', [$type, $preference]) }}" onsubmit="return confirm('Delete this preference from all products? Existing orders will retain their saved details.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">View only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $type === 'pantone' ? 6 : 5 }}" class="px-5 py-12 text-center text-slate-500">
                                <p class="font-semibold text-brand-ink">No {{ strtolower($heading) }} found.</p>
                                <p class="mt-1 text-xs">{{ $search !== '' || $statusFilter !== '' ? 'Adjust your filters to see more results.' : 'Add your first record using the button above.' }}</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-xs text-slate-500">
                <span>Showing {{ $preferences->firstItem() ?? 0 }}–{{ $preferences->lastItem() ?? 0 }} of {{ $preferences->total() }} records</span>
                @if($preferences->hasPages()){{ $preferences->links('pagination.nextplay-simple') }}@endif
            </div>
        </div>
        @if($type === 'pantone')
            <p class="text-xs text-slate-500">Pantone codes are production references. Screen swatches use the HEX value saved with each color; they are not official Pantone conversions.</p>
        @endif
    </div>
</x-layouts.admin>
