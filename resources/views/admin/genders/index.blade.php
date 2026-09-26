<x-layouts.admin title="Gender Master Data" subtitle="Manage reusable gender values for products.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Create and maintain the common gender options used by products. Active values appear on the right side of the Add/Edit Product page.</p>
        <a href="{{ route('admin.genders.create') }}" class="btn btn-red">+ Add Gender</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Gender master data table">
            <table class="admin-table min-w-[720px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Gender</th>
                        <th class="px-5 py-4">Products</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Sort</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($genders as $gender)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block font-semibold text-brand-ink">{{ $gender->name }}</strong>
                                <span class="text-xs font-medium text-slate-500">{{ $gender->slug }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ number_format((int) $gender->products_count) }}</td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $gender->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $gender->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $gender->sort_order }}</td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.genders.edit', $gender) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.genders.destroy', $gender) }}" onsubmit="return confirm('Delete this gender? Any assigned products will keep working but their gender will become blank.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No gender values have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $genders->links('pagination.nextplay') }}</div>
</x-layouts.admin>
