<x-layouts.admin title="Products">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-[minmax(240px,1fr)_180px_auto]">
            <label class="admin-label">Search<input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, SKU or slug"></label>
            <label class="admin-label">Status<select class="admin-input" name="status"><option value="">All statuses</option>@foreach(['draft','active','archived'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2"><label class="flex h-12 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold"><input type="checkbox" name="featured" value="1" @checked($filters['featured'] ?? false)> Featured</label><button class="btn btn-white h-12">Filter</button></div>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn btn-red">Add Product</a>
    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.13em] text-slate-500"><tr><th class="px-5 py-4">Product</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">Price</th><th class="px-5 py-4">Inventory</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Flags</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><img src="{{ $product->primaryImageUrl() }}" alt="" class="h-14 w-14 rounded-xl object-cover"><div><a href="{{ route('admin.products.edit', $product) }}" class="font-black text-brand-blue">{{ $product->name }}</a><p class="mt-1 text-xs text-slate-400">{{ $product->sku }} · /product/{{ $product->slug }}</p></div></div></td>
                            <td class="px-5 py-4"><p class="font-bold">{{ $product->category?->name ?? 'Uncategorized' }}</p><p class="text-xs text-slate-400">{{ $product->subcategory?->name ?? 'No subcategory' }}</p></td>
                            <td class="px-5 py-4 font-black">{{ $product->currency }} {{ number_format((float)$product->base_price, 2) }}</td>
                            <td class="px-5 py-4">@if($product->track_inventory)<span class="font-bold {{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-amber-700' : 'text-slate-700' }}">{{ number_format($product->stock_quantity) }} in stock</span>@else<span class="text-slate-400">Not tracked</span>@endif</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $product->status === 'active' && $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($product->status) }}</span></td>
                            <td class="px-5 py-4"><div class="flex flex-wrap gap-1">@if($product->is_featured)<span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-black text-amber-700">Featured</span>@endif @if($product->is_customizable)<span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-black text-brand-blue">Customizable</span>@endif</div></td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('products.show', $product->slug) }}" target="_blank" class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Preview</a><a href="{{ route('admin.products.edit', $product) }}" class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Edit</a><form method="POST" action="{{ route('admin.products.duplicate', $product) }}">@csrf<button class="rounded-lg border border-blue-200 px-3 py-2 font-bold text-brand-blue">Duplicate</button></form><form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Move this product to trash?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 font-bold text-red-700">Delete</button></form></div></td>
                        </tr>
                    @empty<tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No products found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $products->links() }}</div>
    </section>
</x-layouts.admin>
