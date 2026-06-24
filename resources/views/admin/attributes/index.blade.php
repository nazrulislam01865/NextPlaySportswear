<x-layouts.admin title="Catalog Attributes">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-sm text-slate-500">Reusable product attributes power category-specific storefront filters such as color, size, production time, and shipping.</p></div>
        <a href="{{ route('admin.attributes.create') }}" class="btn btn-red">+ Add Attribute</a>
    </div>
    <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:grid-cols-[1fr_180px_auto]" method="GET">
        <input class="admin-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or slug">
        <select class="admin-input" name="active"><option value="">All statuses</option><option value="1" @selected(($filters['active'] ?? '')==='1')>Active</option><option value="0" @selected(($filters['active'] ?? '')==='0')>Inactive</option></select>
        <button class="btn btn-white">Filter</button>
    </form>
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Catalog attributes table"><table class="admin-table min-w-[760px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Attribute</th><th class="px-5 py-4">Display</th><th class="px-5 py-4">Values</th><th class="px-5 py-4">Categories</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($attributes as $attribute)<tr><td class="px-5 py-4"><strong class="block text-brand-ink">{{ $attribute->name }}</strong><span class="text-xs text-slate-500">{{ $attribute->slug }}</span></td><td class="px-5 py-4">{{ ucfirst($attribute->display_type) }}</td><td class="px-5 py-4">{{ $attribute->values_count }}</td><td class="px-5 py-4">{{ $attribute->categories_count }}</td><td class="px-5 py-4"><span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $attribute->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $attribute->is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-5 py-4"><div class="admin-row-actions"><a class="admin-row-action border-slate-200" href="{{ route('admin.attributes.edit',$attribute) }}">Edit</a><form method="POST" action="{{ route('admin.attributes.destroy',$attribute) }}" onsubmit="return confirm('Delete this attribute?');">@csrf @method('DELETE')<button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button></form></div></td></tr>
        @empty<tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No catalog attributes found.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="mt-5">{{ $attributes->links() }}</div>
</x-layouts.admin>
