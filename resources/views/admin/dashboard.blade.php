<x-layouts.admin title="Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Products', $stats['products'], route('admin.products.index')],
            ['Active Products', $stats['active_products'], route('admin.products.index', ['status' => 'active'])],
            ['Featured Products', $stats['featured_products'], route('admin.products.index', ['featured' => 1])],
            ['Low Stock', $stats['low_stock_products'], route('admin.modules.show', 'inventory')],
            ['Categories', $stats['categories'], route('admin.categories.index')],
            ['Subcategories', $stats['subcategories'], route('admin.categories.index')],
            ['Customers', $stats['customers'], route('admin.modules.show', 'customers')],
            ['Homepage Slides', $stats['active_slides'], route('admin.homepage-slides.index')],
        ] as [$label, $value, $url])
            <a href="{{ $url }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft">
                <p class="text-xs font-black uppercase tracking-[.14em] text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-4xl font-black text-brand-dark">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div><h2 class="text-xl font-black">Recent products</h2><p class="text-sm text-slate-500">Newest catalog records and publication status.</p></div>
                <a href="{{ route('admin.products.create') }}" class="btn btn-red">Add Product</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Price</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentProducts as $product)
                            <tr><td class="px-5 py-4"><a class="font-black text-brand-blue" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a><p class="text-xs text-slate-400">{{ $product->sku }}</p></td><td class="px-5 py-4 text-slate-600">{{ $product->subcategory?->name ?? $product->category?->name ?? 'Uncategorized' }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black">{{ ucfirst($product->status) }}</span></td><td class="px-5 py-4 font-black">{{ $product->currency }} {{ number_format((float)$product->base_price, 2) }}</td></tr>
                        @empty<tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No products yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl bg-brand-dark p-6 text-white shadow-card">
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Catalog control</p>
            <h2 class="mt-2 text-2xl font-black">Everything required for flexible products</h2>
            <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-300">
                <li>✓ Category and subcategory assignment</li><li>✓ Featured or normal product status</li><li>✓ Rich product descriptions</li><li>✓ Product-specific customization groups</li><li>✓ Flexible quantity pricing tables</li><li>✓ Images, sizes, artwork and production options</li><li>✓ SEO title, description, slug, canonical, OG and schema</li><li>✓ Inventory, backorder, tax and shipping settings</li>
            </ul>
        </section>
    </div>
</x-layouts.admin>
