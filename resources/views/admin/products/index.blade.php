<x-layouts.admin
    title="Products"
    subtitle="Manage products, pricing, visibility, inventory, and storefront readiness."
>
    @php
        $productStats = $productStats ?? [];
        $statCards = [
            [
                'key' => 'total',
                'label' => 'Total Products',
                'value' => $productStats['total'] ?? 0,
                'note' => 'All products in catalog',
                'icon' => '▧',
                'iconClass' => 'bg-blue-50 text-brand-blue',
            ],
            [
                'key' => 'active',
                'label' => 'Active',
                'value' => $productStats['active'] ?? 0,
                'note' => 'Visible on storefront',
                'icon' => '✓',
                'iconClass' => 'bg-emerald-50 text-emerald-700',
            ],
            [
                'key' => 'draft_incomplete',
                'label' => 'Draft / Incomplete',
                'value' => $productStats['draft_incomplete'] ?? 0,
                'note' => 'Need completion',
                'icon' => '✎',
                'iconClass' => 'bg-amber-50 text-amber-700',
            ],
            [
                'key' => 'customizable',
                'label' => 'Customizable',
                'value' => $productStats['customizable'] ?? 0,
                'note' => 'Personalized products',
                'icon' => '☆',
                'iconClass' => 'bg-violet-50 text-violet-700',
            ],
            [
                'key' => 'inventory_not_tracked',
                'label' => 'Inventory Not Tracked',
                'value' => $productStats['inventory_not_tracked'] ?? 0,
                'note' => 'Check stock settings',
                'icon' => '◈',
                'iconClass' => 'bg-orange-50 text-orange-700',
            ],
        ];
        $hasActiveFilters = filled($filters['q'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['category_id'] ?? null)
            || (bool) ($filters['featured'] ?? false);
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" data-product-stats data-product-stats-url="{{ route('admin.products.stats') }}">
        @foreach($statCards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex items-center gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full text-xl font-black {{ $card['iconClass'] }}">{{ $card['icon'] }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-700">{{ $card['label'] }}</p>
                        <p class="mt-1 text-3xl font-black leading-none text-brand-ink" data-product-stat-value="{{ $card['key'] }}" aria-live="polite">{{ number_format((int) $card['value']) }}</p>
                        <p class="mt-2 truncate text-xs font-semibold text-slate-400">{{ $card['note'] }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <form method="GET" class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(260px,1fr)_220px_240px_auto_auto]">
            <label class="admin-label relative" data-product-search data-product-search-url="{{ route('admin.products.suggestions') }}">
                Search
                <input
                    class="admin-input"
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Name, SKU or slug"
                    autocomplete="off"
                    data-product-search-input
                    aria-autocomplete="list"
                    aria-expanded="false"
                >
                <div class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" data-product-search-panel>
                    <div class="max-h-80 overflow-y-auto py-2" data-product-search-results></div>
                </div>
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
            <label class="admin-label">
                Category
                <select class="admin-input" name="category_id">
                    <option value="">All categories</option>
                    @foreach($categoryOptions as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->indented_name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col justify-end">
                <label class="flex h-12 min-w-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold shadow-sm">
                    <input class="h-4 w-4 rounded border-slate-300" type="checkbox" name="featured" value="1" @checked($filters['featured'] ?? false)>
                    <span class="whitespace-nowrap">Featured</span>
                </label>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <button class="btn btn-navy h-12 w-full whitespace-nowrap sm:w-auto">◇ Filter</button>
                @if($hasActiveFilters)
                    <a href="{{ route('admin.products.index') }}" class="btn btn-white h-12 w-full whitespace-nowrap sm:w-auto">Clear</a>
                @endif
            </div>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn btn-red h-12 shrink-0 whitespace-nowrap">＋ Add Product</a>
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
        <div class="border-t border-slate-100 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-500">
                    @if($products->total() > 0)
                        Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                    @else
                        Showing 0 products
                    @endif
                </p>
                <div class="admin-pagination">{{ $products->onEachSide(1)->links() }}</div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const container = document.querySelector('[data-product-stats]');
            if (!container) return;

            const endpoint = container.dataset.productStatsUrl;
            if (!endpoint) return;

            const formatter = new Intl.NumberFormat(document.documentElement.lang || undefined);
            let refreshInProgress = false;

            const applyStats = (stats = {}) => {
                Object.entries(stats).forEach(([key, value]) => {
                    const numericValue = Number(value || 0);
                    container.querySelectorAll(`[data-product-stat-value="${key}"]`).forEach((element) => {
                        element.textContent = formatter.format(numericValue);
                        element.dataset.liveValue = String(numericValue);
                    });
                });
            };

            const refresh = async () => {
                if (refreshInProgress) return;
                refreshInProgress = true;

                try {
                    const response = await fetch(endpoint, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    if (!response.ok) return;

                    const payload = await response.json();
                    applyStats(payload.data || payload);
                } catch (error) {
                    // Keep the server-rendered counts visible if the live refresh cannot complete.
                } finally {
                    refreshInProgress = false;
                }
            };

            refresh();
            window.setInterval(refresh, 20000);
            window.addEventListener('focus', refresh);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refresh();
            });
        })();
    </script>


    <script>
        (() => {
            const wrapper = document.querySelector('[data-product-search]');
            if (!wrapper) return;

            const input = wrapper.querySelector('[data-product-search-input]');
            const panel = wrapper.querySelector('[data-product-search-panel]');
            const resultsBox = wrapper.querySelector('[data-product-search-results]');
            const form = wrapper.closest('form');
            const endpoint = wrapper.dataset.productSearchUrl;

            if (!input || !panel || !resultsBox || !endpoint) return;

            let activeIndex = -1;
            let suggestions = [];
            let debounceTimer = null;
            let abortController = null;

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const openPanel = () => {
                panel.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
            };

            const closePanel = () => {
                panel.classList.add('hidden');
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
                updateActiveSuggestion();
            };

            const updateActiveSuggestion = () => {
                resultsBox.querySelectorAll('[data-product-suggestion-item]').forEach((item, index) => {
                    item.classList.toggle('bg-slate-50', index === activeIndex);
                });
            };

            const submitWithSuggestion = (suggestion) => {
                if (!suggestion) return;

                input.value = suggestion.search_value || suggestion.sku || suggestion.name || '';
                closePanel();

                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            };

            const renderSuggestions = (items, query) => {
                suggestions = Array.isArray(items) ? items : [];
                activeIndex = -1;

                if (suggestions.length === 0) {
                    resultsBox.innerHTML = `
                        <div class="px-4 py-4 text-sm font-semibold text-slate-500">
                            No matching products found${query ? ` for “${escapeHtml(query)}”` : ''}.
                        </div>
                    `;
                    openPanel();
                    return;
                }

                resultsBox.innerHTML = suggestions.map((item, index) => `
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
                        data-product-suggestion-item
                        data-product-suggestion-index="${index}"
                    >
                        <img src="${escapeHtml(item.image_url)}" alt="" class="h-11 w-11 shrink-0 rounded-xl border border-slate-100 object-cover">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-black text-brand-ink">${escapeHtml(item.name)}</span>
                            <span class="mt-0.5 block truncate text-xs font-semibold text-slate-400">
                                ${escapeHtml(item.sku || 'No SKU')} · ${escapeHtml(item.category || 'Uncategorized')}
                            </span>
                        </span>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">
                            ${escapeHtml(item.status || 'Draft')}
                        </span>
                    </button>
                `).join('');

                resultsBox.querySelectorAll('[data-product-suggestion-item]').forEach((button) => {
                    button.addEventListener('mousedown', (event) => event.preventDefault());
                    button.addEventListener('click', () => {
                        const index = Number(button.dataset.productSuggestionIndex || -1);
                        submitWithSuggestion(suggestions[index]);
                    });
                });

                openPanel();
            };

            const fetchSuggestions = async () => {
                const query = input.value.trim();
                const url = new URL(endpoint, window.location.origin);
                if (query !== '') {
                    url.searchParams.set('q', query);
                }

                if (abortController) {
                    abortController.abort();
                }

                abortController = new AbortController();

                resultsBox.innerHTML = '<div class="px-4 py-4 text-sm font-semibold text-slate-500">Searching products...</div>';
                openPanel();

                try {
                    const response = await fetch(url.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                        signal: abortController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Product suggestion request failed.');
                    }

                    const payload = await response.json();
                    renderSuggestions(payload.data || [], payload.query || query);
                } catch (error) {
                    if (error.name === 'AbortError') return;

                    suggestions = [];
                    resultsBox.innerHTML = '<div class="px-4 py-4 text-sm font-semibold text-red-600">Could not load suggestions. Please try again.</div>';
                    openPanel();
                }
            };

            const scheduleFetch = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(fetchSuggestions, 180);
            };

            input.addEventListener('input', scheduleFetch);
            input.addEventListener('focus', scheduleFetch);

            input.addEventListener('keydown', (event) => {
                if (panel.classList.contains('hidden')) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeIndex = suggestions.length === 0 ? -1 : Math.min(activeIndex + 1, suggestions.length - 1);
                    updateActiveSuggestion();
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeIndex = suggestions.length === 0 ? -1 : Math.max(activeIndex - 1, 0);
                    updateActiveSuggestion();
                    return;
                }

                if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    submitWithSuggestion(suggestions[activeIndex]);
                    return;
                }

                if (event.key === 'Escape') {
                    closePanel();
                }
            });

            document.addEventListener('click', (event) => {
                if (!wrapper.contains(event.target)) {
                    closePanel();
                }
            });
        })();
    </script>

</x-layouts.admin>
