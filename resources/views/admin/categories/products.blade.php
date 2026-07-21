<x-layouts.admin title="Products in {{ $category->name }}" :compact-header="true">
    @php
        $searchValue = $filters['q'] ?? '';
        $from = $products->firstItem();
        $to = $products->lastItem();
        $total = $products->total();
        $optionDepthPadding = static fn ($item): string => str_repeat('— ', max(0, (int) $item->depth - (int) $category->depth - 1));
    @endphp

    <div class="space-y-6" data-category-products-page>
        <section class="rounded-[18px] border border-slate-200 bg-white p-5 shadow-card sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
                        <a href="{{ route('admin.categories.index') }}" class="text-brand-blue hover:text-brand-navy">Categories</a>
                        @foreach($breadcrumbs as $crumb)
                            <span class="text-slate-300">/</span>
                            @if($loop->last)
                                <span class="text-brand-ink" aria-current="page">{{ $crumb->name }}</span>
                            @else
                                <a href="{{ route('admin.categories.products.index', $crumb) }}" class="hover:text-brand-blue">{{ $crumb->name }}</a>
                            @endif
                        @endforeach
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                            {{ number_format($categoryProductCount) }} {{ \Illuminate\Support\Str::plural('product', $categoryProductCount) }}
                        </span>
                    </nav>
                    <p class="mt-3 max-w-4xl text-sm font-medium leading-6 text-slate-500">
                        This list is filtered to <strong class="font-black text-brand-ink">{{ $category->name }}</strong> only. Use the tags to review parent, primary, and optional subcategories, then apply changes.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.categories.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-brand-navy shadow-sm transition hover:bg-slate-50">
                        Back to categories
                    </a>
                    <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-4 text-sm font-extrabold text-white shadow-card transition hover:-translate-y-0.5 hover:bg-brand-dark">
                        Edit category
                    </a>
                </div>
            </div>
        </section>

        <form
            method="GET"
            action="{{ route('admin.categories.products.index', $category) }}"
            class="rounded-[18px] border border-slate-200 bg-white p-5 shadow-card sm:p-6"
        >
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_150px]">
                <label class="relative block">
                    <span class="sr-only">Search product, SKU, or slug</span>
                    <span class="pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="m21 21-4.3-4.3M10.8 18.2a7.4 7.4 0 1 1 0-14.8 7.4 7.4 0 0 1 0 14.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                    </span>
                    <input
                        class="h-[54px] w-full rounded-xl border border-slate-300 bg-white pl-14 pr-5 text-sm font-semibold text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-blue-100"
                        name="q"
                        value="{{ $searchValue }}"
                        placeholder="Search products inside {{ $category->name }}"
                        autocomplete="off"
                        maxlength="100"
                    >
                </label>

                <button class="inline-flex min-h-[54px] w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-5 text-sm font-extrabold text-brand-navy shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" type="submit">
                    Filter
                </button>
            </div>
        </form>

        <form id="category-products-form" method="POST" action="{{ route('admin.categories.products.update', $category) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <section class="category-product-bulk-card" aria-label="Bulk category assignment">
                <div class="category-product-bulk-left">
                    <label class="inline-flex items-center gap-3 text-sm font-black text-brand-ink">
                        <input id="category-product-check-all" type="checkbox" class="h-5 w-5 rounded border-slate-300 accent-brand-blue" aria-label="Select all visible products">
                        <span><span data-selected-count>0</span> selected</span>
                    </label>
                </div>

                <details class="category-picker category-picker--bulk" data-category-picker>
                    <summary>
                        <span>Assign category</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                    </summary>
                    <div class="category-picker-panel">
                        <label class="category-picker-search">
                            <span class="sr-only">Search categories</span>
                            <input type="search" placeholder="Search categories..." data-category-picker-search>
                        </label>
                        <div class="category-picker-options" data-category-picker-options>
                            @foreach($bulkCategoryOptions as $option)
                                <label class="category-picker-option" data-category-picker-option data-search="{{ \Illuminate\Support\Str::lower($option->name.' '.$option->slug) }}">
                                    <input type="checkbox" name="bulk_category_ids[]" value="{{ $option->id }}">
                                    <span>{{ str_repeat('— ', max(0, (int) $option->depth)) }}{{ $option->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button class="category-picker-apply" type="submit">Apply</button>
                    </div>
                </details>

                <p class="category-product-bulk-note">Select products, choose one or more categories, then Apply. Primary category tags are protected.</p>
            </section>

            <section class="overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-card">
                <div class="category-product-assignment-scroll" tabindex="0" aria-label="Category product assignments table">
                    <table class="category-product-assignment-table category-product-assignment-table--simple w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-white text-left text-[11px] font-black uppercase tracking-[.18em] text-slate-500">
                                <th class="w-[64px] px-6 py-5">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th class="w-[32%] px-6 py-5">Product</th>
                                <th class="w-[42%] px-6 py-5">Category &amp; Subcategories</th>
                                <th class="w-[12%] px-6 py-5">Visibility</th>
                                <th class="w-[14%] px-6 py-5">Updated</th>
                                <th class="w-[64px] px-6 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                                @php
                                    $assignedCategories = $product->categories->values();
                                    $primaryCategory = $assignedCategories->first(fn ($item) => (bool) ($item->pivot?->is_primary ?? false))
                                        ?? $assignedCategories->firstWhere('id', $product->subcategory_id)
                                        ?? $assignedCategories->firstWhere('id', $category->id)
                                        ?? $assignedCategories->first();
                                    $parentCategory = $primaryCategory?->parent_id
                                        ? ($assignedCategories->firstWhere('id', $primaryCategory->parent_id) ?? $primaryCategory->parent)
                                        : null;
                                    $protectedCategoryIds = collect([$parentCategory?->id, $primaryCategory?->id, $category->id])
                                        ->filter()
                                        ->map(fn ($id) => (int) $id)
                                        ->unique()
                                        ->values();
                                    $optionalCategories = $assignedCategories
                                        ->reject(fn ($item) => $protectedCategoryIds->contains((int) $item->id))
                                        ->values();
                                    $existingCategoryIds = $assignedCategories->pluck('id')->map(fn ($id) => (int) $id)->flip();
                                    $rowSubcategoryOptions = $subcategoryOptions
                                        ->reject(fn ($option) => $existingCategoryIds->has((int) $option->id))
                                        ->values();
                                @endphp
                                <tr class="align-middle transition hover:bg-slate-50/60">
                                    <td class="px-6 py-6" data-label="Select">
                                        <input type="hidden" name="visible_product_ids[]" value="{{ $product->id }}">
                                        <input
                                            type="checkbox"
                                            name="selected_product_ids[]"
                                            value="{{ $product->id }}"
                                            class="category-product-row-check h-5 w-5 rounded border-slate-300 accent-brand-blue"
                                            aria-label="Select {{ $product->name }}"
                                            data-product-row-check
                                        >
                                    </td>

                                    <td class="px-6 py-6" data-label="Product" data-card-cell="product">
                                        <div class="flex min-w-0 items-center gap-5">
                                            <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 object-cover" loading="lazy" decoding="async">
                                            <div class="min-w-0">
                                                <strong class="block text-sm font-black leading-6 text-brand-ink">{{ $product->name }}</strong>
                                                <span class="mt-1 block font-mono text-xs font-semibold tracking-wide text-slate-500">{{ $product->sku }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-6" data-label="Category & Subcategories" data-card-cell="categories">
                                        <div class="category-tag-stack">
                                            <div class="category-tag-row">
                                                @if($parentCategory)
                                                    <span class="category-assignment-tag category-assignment-tag--parent" title="Parent category">
                                                        {{ $parentCategory->name }}
                                                    </span>
                                                @endif

                                                @if($primaryCategory)
                                                    <span class="category-assignment-tag category-assignment-tag--primary" title="Primary category is protected">
                                                        {{ $primaryCategory->name }}
                                                        <small>Primary</small>
                                                    </span>
                                                @endif

                                                @foreach($optionalCategories as $item)
                                                    <label class="category-assignment-tag category-assignment-tag--optional" title="Mark {{ $item->name }} for removal">
                                                        <input type="checkbox" name="product_category_updates[{{ $product->id }}][remove_category_ids][]" value="{{ $item->id }}" data-remove-tag-checkbox>
                                                        <span>{{ $item->name }}</span>
                                                        <strong aria-hidden="true">×</strong>
                                                    </label>
                                                @endforeach
                                            </div>

                                            <details class="category-picker category-picker--row" data-category-picker>
                                                <summary>
                                                    <span>+ Add subcategory</span>
                                                </summary>
                                                <div class="category-picker-panel">
                                                    <label class="category-picker-search">
                                                        <span class="sr-only">Search subcategories</span>
                                                        <input type="search" placeholder="Search subcategories..." data-category-picker-search>
                                                    </label>
                                                    <div class="category-picker-options" data-category-picker-options>
                                                        @forelse($rowSubcategoryOptions as $option)
                                                            <label class="category-picker-option" data-category-picker-option data-search="{{ \Illuminate\Support\Str::lower($option->name.' '.$option->slug) }}">
                                                                <input type="checkbox" name="product_category_updates[{{ $product->id }}][add_category_ids][]" value="{{ $option->id }}">
                                                                <span>{{ $optionDepthPadding($option) }}{{ $option->name }}</span>
                                                            </label>
                                                        @empty
                                                            <p class="category-picker-empty">No more subcategories available.</p>
                                                        @endforelse
                                                    </div>
                                                    <button class="category-picker-apply" type="submit">Apply</button>
                                                </div>
                                            </details>
                                        </div>
                                    </td>

                                    <td class="px-6 py-6" data-label="Visibility">
                                        <span class="admin-status-pill px-3 py-1.5 text-xs font-black {{ $product->status === 'active' && $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $product->status === 'active' && $product->is_active ? 'Active' : ucfirst($product->status) }}
                                        </span>
                                        <p class="mt-2 text-xs font-medium text-slate-500">Catalog: {{ $product->is_active ? 'Yes' : 'No' }}</p>
                                    </td>

                                    <td class="px-6 py-6 text-sm text-slate-500" data-label="Updated">
                                        <span class="whitespace-nowrap">{{ $product->updated_at?->format('M j, Y g:i A') }}</span>
                                        @if($product->updater)
                                            <span class="mt-1 block text-xs">by {{ $product->updater->name }}</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-6 text-right" data-label="Actions">
                                        <details class="category-row-menu">
                                            <summary aria-label="More actions for {{ $product->name }}">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4.5h.01M10 10h.01M10 15.5h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                                            </summary>
                                            <div class="category-row-menu-panel">
                                                <a href="{{ route('admin.products.edit', $product) }}">Edit product</a>
                                                <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener">Preview</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-16 text-center">
                                        <p class="text-lg font-black text-brand-ink">No products found</p>
                                        <p class="mt-2 text-sm font-medium text-slate-500">This category has no matching products yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-sm font-medium text-slate-600">
                        @if($total > 0)
                            Showing {{ $from }}–{{ $to }} of {{ $total }} filtered {{ \Illuminate\Support\Str::plural('product', $total) }} in {{ $category->name }}
                        @else
                            Showing 0 products in {{ $category->name }}
                        @endif
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <div class="admin-pagination">{{ $products->onEachSide(1)->links() }}</div>
                        @if($products->count() > 0)
                            <button class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-navy px-5 text-sm font-extrabold text-white shadow-card transition hover:-translate-y-0.5 hover:bg-brand-dark" type="submit">
                                Apply Changes
                            </button>
                        @endif
                    </div>
                </div>
            </section>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.querySelector('[data-category-products-page]');
            if (!page) return;

            const rowChecks = Array.from(page.querySelectorAll('[data-product-row-check]'));
            const checkAll = page.querySelector('#category-product-check-all');
            const selectedCount = page.querySelector('[data-selected-count]');

            const updateSelectedCount = () => {
                const count = rowChecks.filter((checkbox) => checkbox.checked).length;
                if (selectedCount) selectedCount.textContent = String(count);
                if (checkAll) {
                    checkAll.checked = rowChecks.length > 0 && count === rowChecks.length;
                    checkAll.indeterminate = count > 0 && count < rowChecks.length;
                }
            };

            checkAll?.addEventListener('change', () => {
                rowChecks.forEach((checkbox) => { checkbox.checked = checkAll.checked; });
                updateSelectedCount();
            });

            rowChecks.forEach((checkbox) => checkbox.addEventListener('change', updateSelectedCount));
            updateSelectedCount();

            page.querySelectorAll('[data-category-picker]').forEach((picker) => {
                const input = picker.querySelector('[data-category-picker-search]');
                const options = Array.from(picker.querySelectorAll('[data-category-picker-option]'));

                input?.addEventListener('input', () => {
                    const term = input.value.trim().toLowerCase();
                    options.forEach((option) => {
                        const haystack = String(option.dataset.search || '').toLowerCase();
                        option.hidden = term !== '' && !haystack.includes(term);
                    });
                });
            });

            page.querySelectorAll('[data-remove-tag-checkbox]').forEach((checkbox) => {
                const chip = checkbox.closest('.category-assignment-tag');
                checkbox.addEventListener('change', () => {
                    chip?.classList.toggle('is-pending-removal', checkbox.checked);
                });
            });
        });
    </script>
</x-layouts.admin>
