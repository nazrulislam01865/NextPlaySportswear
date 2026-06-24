<x-layouts.admin title="Products in {{ $category->name }}">
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="text-sm leading-6 text-slate-600">Assign products to multiple categories. One assignment can be primary for breadcrumbs and canonical catalog placement; feature and order are category-specific.</p><a href="{{ route('admin.categories.edit',$category) }}" class="mt-2 inline-block text-sm font-black text-brand-blue">← Back to category</a></div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-red">Create Product</a>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[minmax(0,1fr)_220px_auto]">
        <input class="admin-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search product, SKU, or slug">
        <select class="admin-input" name="assignment"><option value="">All products</option><option value="assigned" @selected(($filters['assignment'] ?? '')==='assigned')>Assigned only</option><option value="unassigned" @selected(($filters['assignment'] ?? '')==='unassigned')>Unassigned only</option></select>
        <button class="btn btn-navy">Filter</button>
    </form>

    <form method="POST" action="{{ route('admin.categories.products.update',$category) }}">
        @csrf @method('PUT')
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="admin-table-scroll" tabindex="0" aria-label="Category product assignments table">
                <table class="admin-table min-w-[1050px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.13em] text-slate-500"><tr><th class="px-5 py-4">Assigned</th><th class="px-5 py-4">Product</th><th class="px-5 py-4">Current categories</th><th class="px-5 py-4">Primary</th><th class="px-5 py-4">Featured here</th><th class="px-5 py-4">Order</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($products as $product)
                            @php($assignedCategory = $product->categories->firstWhere('id',$category->id))
                            <tr>
                                <td class="px-5 py-4"><input type="hidden" name="visible_product_ids[]" value="{{ $product->id }}"><input type="hidden" name="assignments[{{ $product->id }}][assigned]" value="0"><input type="checkbox" name="assignments[{{ $product->id }}][assigned]" value="1" @checked((bool)$assignedCategory) class="h-5 w-5 rounded border-slate-300 text-brand-red"></td>
                                <td class="px-5 py-4"><div class="flex items-center gap-3"><img src="{{ $product->primaryImageUrl() }}" alt="" class="h-14 w-14 rounded-xl border border-slate-200 object-cover"><div><strong class="block">{{ $product->name }}</strong><span class="font-mono text-xs text-slate-500">{{ $product->sku }}</span></div></div></td>
                                <td class="px-5 py-4"><div class="flex max-w-sm flex-wrap gap-1">@forelse($product->categories as $item)<span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold">{{ $item->name }} @if($item->pivot->is_primary)<span aria-label="Primary category">· Primary</span>@endif</span>@empty<span class="text-slate-400">None</span>@endforelse</div></td>
                                <td class="px-5 py-4"><input type="hidden" name="assignments[{{ $product->id }}][is_primary]" value="0"><input type="checkbox" name="assignments[{{ $product->id }}][is_primary]" value="1" @checked($assignedCategory?->pivot?->is_primary) class="h-5 w-5 rounded border-slate-300 text-brand-blue"></td>
                                <td class="px-5 py-4"><input type="hidden" name="assignments[{{ $product->id }}][is_featured]" value="0"><input type="checkbox" name="assignments[{{ $product->id }}][is_featured]" value="1" @checked($assignedCategory?->pivot?->is_featured) class="h-5 w-5 rounded border-slate-300 text-amber-500"></td>
                                <td class="px-5 py-4"><input class="admin-input w-28" type="number" min="0" name="assignments[{{ $product->id }}][sort_order]" value="{{ $assignedCategory?->pivot?->sort_order ?? 0 }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"><div>{{ $products->links() }}</div><button class="btn btn-red">Save Visible Assignments</button></div>
        </section>
    </form>
</x-layouts.admin>
