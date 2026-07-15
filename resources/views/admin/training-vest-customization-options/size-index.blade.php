<x-layouts.admin
    title="Training Vest Size Options"
    :eyebrow="'Master Data / '.$type->groupNumber().' '.$type->groupLabel()"
    :subtitle="$type->helpText()"
    compact-header
>
    <div class="space-y-6">
        @include('admin.training-vest-customization-options._training-vest-tabs')

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-blue">Training Vest Customization</p>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Manage training vest size-chart groups only. These sizes are stored separately and do not mix with jersey, sweatshirt, jacket, or other size options.</p>
            </div>
            <a href="{{ route('admin.training-vest-size-option-groups.create') }}" class="btn btn-red">+ Add Size Group</a>
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
            <div class="admin-table-scroll" tabindex="0" aria-label="Training vest size option groups table">
                <table class="admin-table min-w-[980px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Name</th>
                            <th class="px-5 py-4">Type</th>
                            <th class="px-5 py-4">Available sizes</th>
                            <th class="px-5 py-4">Size chart</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($groups as $group)
                            <tr>
                                <td class="px-5 py-4"><strong class="text-brand-ink">{{ $group->name }}</strong></td>
                                <td class="px-5 py-4 font-semibold text-slate-700">{{ $group->audience->label() }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex max-w-md flex-wrap gap-1.5">
                                        @foreach($group->sizes->take(10) as $size)
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $size->label }}</span>
                                        @endforeach
                                        @if($group->sizes_count > 10)
                                            <span class="text-xs text-slate-500">+{{ $group->sizes_count - 10 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">{{ filled($group->chart_html) ? 'Formatted content' : ($group->chartImageUrl() ? 'Image configured' : '—') }}</td>
                                <td class="px-5 py-4">
                                    <div class="admin-row-actions">
                                        <a class="admin-row-action border-slate-200" href="{{ route('admin.training-vest-size-option-groups.edit', $group) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.training-vest-size-option-groups.destroy', $group) }}" onsubmit="return confirm('Delete this training vest size option group?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No training vest size option groups found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $groups->links() }}</div>
    </div>
</x-layouts.admin>
