<x-layouts.admin title="Production Method Master Data" subtitle="Create reusable production timelines and assign them from the product add/edit page.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Manage the production options that products can use. Each method controls the customer-facing name, production timeline, description, and status.</p>
        <a href="{{ route('admin.production-methods.create') }}" class="btn btn-red">+ Add Production Method</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Production methods table">
            <table class="admin-table min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Method</th>
                        <th class="px-5 py-4">Timeline</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($methods as $method)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block font-semibold text-brand-ink">{{ $method->name }}</strong>
                                <span class="text-xs font-medium text-slate-500">{{ $method->code }}</span>
                                @if($method->description)<p class="mt-1 max-w-sm text-xs font-normal leading-5 text-slate-500">{{ $method->description }}</p>@endif
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $method->minimum_days }}–{{ $method->maximum_days }} working days
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($method->is_default)<span class="admin-status-pill bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Default</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.production-methods.edit', $method) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.production-methods.destroy', $method) }}" onsubmit="return confirm('Delete this production method?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-14 text-center text-slate-500">No production methods have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $methods->links('pagination.nextplay') }}</div>
</x-layouts.admin>
