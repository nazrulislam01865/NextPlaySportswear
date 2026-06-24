<x-layouts.admin title="Size Options">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-500">Manage reusable Male, Female, Youth, Kids, Unisex, and custom size groups with size options and measurement charts.</p>
        <a href="{{ route('admin.size-option-groups.create') }}" class="btn btn-red">+ Add Size Group</a>
    </div>

    <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card md:grid-cols-[minmax(0,1fr)_240px_auto]" method="GET">
        <input class="admin-input mt-0" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search size group">
        <select class="admin-input mt-0" name="audience">
            <option value="">All types</option>
            @foreach($audiences as $value => $label)
                <option value="{{ $value }}" @selected(($filters['audience'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Size option groups table">
            <table class="admin-table min-w-[980px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Name</th><th class="px-5 py-4">Type</th><th class="px-5 py-4">Available sizes</th><th class="px-5 py-4">Size chart</th><th class="px-5 py-4">Products</th><th class="px-5 py-4 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($groups as $group)
                        <tr>
                            <td class="px-5 py-4"><strong class="text-brand-ink">{{ $group->name }}</strong></td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $group->audience->label() }}</td>
                            <td class="px-5 py-4"><div class="flex max-w-md flex-wrap gap-1.5">@foreach($group->sizes->take(10) as $size)<span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $size->label }}</span>@endforeach @if($group->sizes_count > 10)<span class="text-xs text-slate-500">+{{ $group->sizes_count - 10 }}</span>@endif</div></td>
                            <td class="px-5 py-4">{{ filled($group->chart_html) ? 'Formatted content' : ($group->chartImageUrl() ? 'Image configured' : '—') }}</td>
                            <td class="px-5 py-4">{{ $group->product_groups_count }}</td>
                            <td class="px-5 py-4"><div class="admin-row-actions">
                                <a class="admin-row-action border-slate-200" href="{{ route('admin.size-option-groups.edit', $group) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.size-option-groups.destroy', $group) }}" onsubmit="return confirm('Delete this size option group? Existing products keep their saved size data.');">@csrf @method('DELETE')<button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No size option groups found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $groups->links() }}</div>
</x-layouts.admin>
