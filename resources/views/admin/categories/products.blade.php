<x-layouts.admin title="Products in {{ $category->name }}">
    @php
        $assignmentFilter = $filters['assignment'] ?? '';
        $searchValue = $filters['q'] ?? '';
        $from = $products->firstItem();
        $to = $products->lastItem();
        $total = $products->total();
    @endphp

    <div class="space-y-7">
        <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-base leading-8 text-slate-600 sm:text-lg">
                    Assign products to multiple categories. One assignment can be primary for breadcrumbs
                    and canonical catalog placement; feature and order are category-specific.
                </p>

                <a
                    href="{{ route('admin.categories.edit', $category) }}"
                    class="mt-5 inline-flex items-center gap-2 text-base font-extrabold text-brand-blue transition hover:text-brand-navy"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Back to category
                </a>
            </div>

            <div class="flex w-full flex-col gap-3 sm:w-auto">
                <form
                    method="POST"
                    action="{{ route('admin.categories.products.sync-legacy') }}"
                    onsubmit="return confirm('This will bulk repair category-product assignments from selected categories, parent categories, and product text matching. Continue?')"
                >
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-14 w-full items-center justify-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-6 text-sm font-extrabold text-emerald-700 shadow-card transition hover:-translate-y-0.5 hover:bg-white"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h10a5 5 0 0 1 5 5v0a5 5 0 0 1-5 5H7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="m8 11-4-4 4-4M16 13l4 4-4 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Auto Assign / Repair / Repair Products
                    </button>
                </form>

                <a
                    href="{{ route('admin.products.create') }}"
                    class="inline-flex min-h-16 w-full items-center justify-center gap-3 rounded-xl bg-brand-navy px-7 text-base font-extrabold text-white shadow-card transition hover:-translate-y-0.5 hover:bg-brand-dark"
                >
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                    </svg>
                    Create Product
                </a>
            </div>
        </section>

        <form
            method="GET"
            action="{{ route('admin.categories.products.index', $category) }}"
            class="rounded-[18px] border border-slate-200 bg-white p-5 shadow-card sm:p-7"
        >
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px_150px]">
                <label class="relative block">
                    <span class="sr-only">Search product, SKU, or slug</span>
                    <span class="pointer-events-none absolute left-6 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                            <path d="m21 21-4.3-4.3M10.8 18.2a7.4 7.4 0 1 1 0-14.8 7.4 7.4 0 0 1 0 14.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input
                        class="h-[68px] w-full rounded-xl border border-slate-300 bg-white pl-16 pr-5 text-base font-medium text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-blue-100"
                        name="q"
                        value="{{ $searchValue }}"
                        placeholder="Search product, SKU, or slug"
                        autocomplete="off"
                        maxlength="100"
                    >
                </label>

                <label class="relative block">
                    <span class="sr-only">Assignment filter</span>
                    <select
                        class="h-[68px] w-full appearance-none rounded-xl border border-slate-300 bg-white px-6 pr-12 text-base font-extrabold text-slate-900 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100"
                        name="assignment"
                    >
                        <option value="">All products</option>
                        <option value="assigned" @selected($assignmentFilter === 'assigned')>Assigned only</option>
                        <option value="unassigned" @selected($assignmentFilter === 'unassigned')>Unassigned only</option>
                    </select>
                    <span class="pointer-events-none absolute right-6 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </label>

                <button
                    class="inline-flex min-h-[68px] w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-slate-50 px-6 text-base font-extrabold text-brand-navy shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100"
                    type="submit"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16M8 12h8M10.5 18h3" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                    </svg>
                    Filter
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.categories.products.update', $category) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <section class="overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-card">
                <div class="category-product-assignment-scroll" tabindex="0" aria-label="Category product assignments table">
                    <table class="category-product-assignment-table w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-white text-left text-[11px] font-black uppercase tracking-[.18em] text-slate-500">
                                <th class="w-[11%] px-8 py-7">Assigned</th>
                                <th class="w-[33%] px-8 py-7">Product</th>
                                <th class="w-[25%] px-8 py-7">Current Categories</th>
                                <th class="w-[9%] px-8 py-7 text-center">Primary</th>
                                <th class="w-[12%] px-8 py-7 text-center">Featured Here</th>
                                <th class="w-[10%] px-8 py-7">Order</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                                @php($assignedCategory = $product->categories->firstWhere('id', $category->id))
                                <tr class="align-middle transition hover:bg-slate-50/60">
                                    <td class="px-8 py-7" data-label="Assigned">
                                        <input type="hidden" name="visible_product_ids[]" value="{{ $product->id }}">
                                        <input type="hidden" name="assignments[{{ $product->id }}][assigned]" value="0">
                                        <input
                                            type="checkbox"
                                            name="assignments[{{ $product->id }}][assigned]"
                                            value="1"
                                            @checked((bool) $assignedCategory)
                                            class="h-7 w-7 rounded-md border-slate-300 accent-brand-blue"
                                            aria-label="Assign {{ $product->name }} to {{ $category->name }}"
                                        >
                                    </td>

                                    <td class="px-8 py-7" data-label="Product" data-card-cell="product">
                                        <div class="flex min-w-0 items-center gap-6">
                                            <img
                                                src="{{ $product->primaryImageUrl() }}"
                                                alt="{{ $product->name }}"
                                                class="h-20 w-20 shrink-0 rounded-2xl border border-slate-200 object-cover"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                            <div class="min-w-0">
                                                <strong class="block text-lg font-black leading-6 text-brand-ink">{{ $product->name }}</strong>
                                                <span class="mt-2 block font-mono text-sm font-semibold tracking-wide text-slate-500">{{ $product->sku }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-8 py-7" data-label="Current Categories" data-card-cell="categories">
                                        <div class="flex max-w-md flex-wrap gap-2">
                                            @forelse($product->categories as $item)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-extrabold leading-none text-brand-ink">
                                                    {{ $item->name }}
                                                    @if($item->pivot->is_primary)
                                                        <span class="ml-1 text-slate-600" aria-label="Primary category">· Primary</span>
                                                    @endif
                                                </span>
                                            @empty
                                                <span class="text-sm font-bold text-slate-400">None</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="px-8 py-7 text-center" data-label="Primary">
                                        <input type="hidden" name="assignments[{{ $product->id }}][is_primary]" value="0">
                                        <input
                                            type="checkbox"
                                            name="assignments[{{ $product->id }}][is_primary]"
                                            value="1"
                                            @checked($assignedCategory?->pivot?->is_primary)
                                            class="h-7 w-7 rounded-md border-slate-300 accent-brand-blue"
                                            aria-label="Make {{ $product->name }} primary in {{ $category->name }}"
                                        >
                                    </td>

                                    <td class="px-8 py-7 text-center" data-label="Featured Here">
                                        <input type="hidden" name="assignments[{{ $product->id }}][is_featured]" value="0">
                                        <input
                                            type="checkbox"
                                            name="assignments[{{ $product->id }}][is_featured]"
                                            value="1"
                                            @checked($assignedCategory?->pivot?->is_featured)
                                            class="h-7 w-7 rounded-md border-slate-300 accent-brand-blue"
                                            aria-label="Feature {{ $product->name }} in {{ $category->name }}"
                                        >
                                    </td>

                                    <td class="px-8 py-7" data-label="Order">
                                        <input
                                            class="h-14 w-36 rounded-xl border border-slate-300 bg-white px-5 text-base font-semibold text-slate-900 shadow-sm outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100"
                                            type="number"
                                            min="0"
                                            max="1000000"
                                            inputmode="numeric"
                                            name="assignments[{{ $product->id }}][sort_order]"
                                            value="{{ $assignedCategory?->pivot?->sort_order ?? 0 }}"
                                            aria-label="Sort order for {{ $product->name }}"
                                        >
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-16 text-center">
                                        <p class="text-lg font-black text-brand-ink">No products found</p>
                                        <p class="mt-2 text-sm font-medium text-slate-500">Try another search keyword or assignment filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-base font-medium text-slate-600">
                        @if($total > 0)
                            Showing {{ $from }}–{{ $to }} of {{ $total }} {{ \Illuminate\Support\Str::plural('product', $total) }}
                        @else
                            Showing 0 products
                        @endif
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <div class="admin-pagination">
                            {{ $products->onEachSide(1)->links() }}
                        </div>
                        @if($products->count() > 0)
                            <button class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-navy px-5 text-sm font-extrabold text-white shadow-card transition hover:-translate-y-0.5 hover:bg-brand-dark">
                                Save Visible Assignments
                            </button>
                        @endif
                    </div>
                </div>
            </section>
        </form>
    </div>
</x-layouts.admin>
