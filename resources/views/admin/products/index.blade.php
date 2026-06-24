<x-layouts.admin title="Products">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <form method="GET" class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(240px,1fr)_180px_auto]">
            <label class="admin-label">
                Search
                <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, SKU or slug">
            </label>
            <label class="admin-label">
                Status
                <select class="admin-input" name="status">
                    <option value="">All statuses</option>
                    @foreach(['draft','active','archived'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <label class="flex h-12 min-w-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold">
                    <input type="checkbox" name="featured" value="1" @checked($filters['featured'] ?? false)>
                    <span class="whitespace-nowrap">Featured</span>
                </label>
                <button class="btn btn-white h-12">Filter</button>
            </div>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn btn-red shrink-0">Add Product</a>
    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Products table">
            <table class="admin-table min-w-[1320px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.13em] text-slate-500">
                    <tr>
                        <th class="w-[330px] px-5 py-4">Product</th>
                        <th class="w-[210px] px-5 py-4">Category</th>
                        <th class="w-[115px] px-5 py-4">Price</th>
                        <th class="w-[135px] px-5 py-4">Inventory</th>
                        <th class="w-[105px] px-5 py-4">Status</th>
                        <th class="w-[150px] px-5 py-4">Flags</th>
                        <th class="w-[350px] px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $product->primaryImageUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-xl object-cover">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="block font-black leading-5 text-brand-blue">{{ $product->name }}</a>
                                        <p class="mt-1 text-xs leading-5 text-slate-400">{{ $product->sku }} · /product/{{ $product->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $product->subcategory?->name ?? 'No subcategory' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 font-black">{{ $product->currency }} {{ number_format((float) $product->base_price, 2) }}</td>
                            <td class="px-5 py-4">
                                @if($product->track_inventory)
                                    <span class="whitespace-nowrap font-bold {{ $product->stock_quantity <= $product->low_stock_threshold ? 'text-amber-700' : 'text-slate-700' }}">{{ number_format($product->stock_quantity) }} in stock</span>
                                @else
                                    <span class="whitespace-nowrap text-slate-400">Not tracked</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-black {{ $product->status === 'active' && $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($product->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($product->is_featured)
                                        <span class="admin-status-pill bg-amber-50 px-2 py-1 text-[10px] font-black text-amber-700">Featured</span>
                                    @endif
                                    @if($product->is_customizable)
                                        <span class="admin-status-pill bg-blue-50 px-2 py-1 text-[10px] font-black text-brand-blue">Customizable</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="admin-row-action border-slate-200">Preview</a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="admin-row-action border-slate-200">Edit</a>
                                    <form method="POST" action="{{ route('admin.products.duplicate', $product) }}">
                                        @csrf
                                        <button class="admin-row-action border-blue-200 text-brand-blue">Duplicate</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Move this product to trash?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $products->links() }}</div>
    </section>
</x-layouts.admin>
