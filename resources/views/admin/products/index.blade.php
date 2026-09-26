<x-layouts.admin title="Products" :compact-header="true">
    @php
        $productStats = $productStats ?? [];
        $statCards = [
            [
                'key' => 'total',
                'label' => 'Total Products',
                'value' => $productStats['total'] ?? 0,
                'note' => 'All products in catalog',
                'icon' => 'products',
                'tone' => 'blue',
            ],
            [
                'key' => 'active',
                'label' => 'Active',
                'value' => $productStats['active'] ?? 0,
                'note' => 'Visible on storefront',
                'icon' => 'check',
                'tone' => 'green',
            ],
            [
                'key' => 'draft_incomplete',
                'label' => 'Draft / Incomplete',
                'value' => $productStats['draft_incomplete'] ?? 0,
                'note' => 'Need completion',
                'icon' => 'edit',
                'tone' => 'amber',
            ],
            [
                'key' => 'customizable',
                'label' => 'Customizable',
                'value' => $productStats['customizable'] ?? 0,
                'note' => 'Personalized products',
                'icon' => 'star',
                'tone' => 'violet',
            ],
            [
                'key' => 'inventory_not_tracked',
                'label' => 'Inventory Not Tracked',
                'value' => $productStats['inventory_not_tracked'] ?? 0,
                'note' => 'Check stock settings',
                'icon' => 'inventory',
                'tone' => 'orange',
            ],
        ];

        $hasActiveFilters = filled($filters['q'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['category_id'] ?? null)
            || (bool) ($filters['featured'] ?? false);
    @endphp

    <div class="product-admin-page">
        <section class="product-stats-grid" aria-label="Product summary" data-product-stats data-product-stats-url="{{ route('admin.products.stats') }}">
            @foreach($statCards as $card)
                <article class="product-stat-card">
                    <span class="product-stat-icon product-stat-icon--{{ $card['tone'] }}" aria-hidden="true">
                        @switch($card['icon'])
                            @case('products')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.2 12 3l7.5 4.2v9.6L12 21l-7.5-4.2V7.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m4.8 7.4 7.2 4 7.2-4M12 11.4V21" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                @break
                            @case('check')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/><path d="m8.5 12.2 2.2 2.2 4.9-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @break
                            @case('edit')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m14.3 5.2 4.5 4.5M6 18l2.1-5.4L16.8 4a1.7 1.7 0 0 1 2.4 0l.8.8a1.7 1.7 0 0 1 0 2.4l-8.6 8.7L6 18Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M5.5 20h13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('star')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m12 3.8 2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6L7.1 19l.9-5.5-4-3.9 5.5-.8 2.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 6.5h14v12H5v-12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 10h8M8 13.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @endswitch
                    </span>
                    <div class="product-stat-copy">
                        <p class="product-stat-label">{{ $card['label'] }}</p>
                        <strong data-product-stat-value="{{ $card['key'] }}" aria-live="polite">{{ number_format((int) $card['value']) }}</strong>
                        <span>{{ $card['note'] }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="product-filter-card" aria-label="Product filters">
            <form method="GET" class="product-filter-form">
                <label class="product-filter-field product-search-field" data-product-search data-product-search-url="{{ route('admin.products.suggestions') }}">
                    <span class="sr-only">Search products</span>
                    <span class="product-filter-leading-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m21 21-4.3-4.3M10.8 18.2a7.4 7.4 0 1 1 0-14.8 7.4 7.4 0 0 1 0 14.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input
                        class="admin-input product-control product-search-control"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Search name, SKU or slug"
                        autocomplete="off"
                        data-product-search-input
                        aria-autocomplete="list"
                        aria-expanded="false"
                    >
                    <div class="product-search-panel" data-product-search-panel hidden>
                        <div class="product-search-results" data-product-search-results></div>
                    </div>
                </label>

                <label class="product-filter-field product-status-field">
                    <span class="sr-only">Product status</span>
                    <select class="admin-input product-control product-select-control" name="status">
                        <option value="">All statuses</option>
                        @foreach(['draft','active','archived'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <span class="product-filter-select-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                </label>

                <label class="product-filter-field product-category-field">
                    <span class="sr-only">Product category</span>
                    <select class="admin-input product-control product-select-control" name="category_id">
                        <option value="">All categories</option>
                        @foreach($categoryOptions as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->indented_name }}</option>
                        @endforeach
                    </select>
                    <span class="product-filter-select-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                </label>

                <label class="product-featured-filter">
                    <input type="checkbox" name="featured" value="1" @checked($filters['featured'] ?? false)>
                    <span>Featured</span>
                </label>

                <div class="product-filter-actions">
                    <button class="product-filter-button" type="submit">Filter</button>
                    @if($hasActiveFilters)
                        <a href="{{ route('admin.products.index') }}" class="product-filter-clear">Clear</a>
                    @endif
                </div>

                <a href="{{ route('admin.products.create') }}" class="product-add-button">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Add Product
                </a>
            </form>
        </section>

        <form id="bulk-product-form" method="POST" action="{{ route('admin.products.bulk') }}" class="product-bulk-card" data-product-bulk-form>
            @csrf
            <strong>Selected products</strong>
            <select class="admin-input product-control product-bulk-select" name="action" required data-product-bulk-action>
                <option value="">Choose bulk action</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate / Draft</option>
                <option value="archive">Archive</option>
                <option value="feature">Mark featured</option>
                <option value="unfeature">Remove featured</option>
                <option value="delete">Delete / Move to trash</option>
            </select>
            <button class="product-bulk-button" type="submit">Apply</button>
            <span class="product-bulk-note">Bulk changes are validated and recorded server-side.</span>
        </form>

        <section class="product-table-card">
            <div class="product-table-scroll" tabindex="0" aria-label="Products table">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th class="product-select-column"><input id="product-check-all" type="checkbox" aria-label="Select all products on this page"></th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Flags</th>
                            <th>Last updated</th>
                            <th>What was updated</th>
                            <th class="product-actions-column"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="product-select-column">
                                    <input class="product-row-check" type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="bulk-product-form" aria-label="Select {{ $product->name }}">
                                </td>
                                <td>
                                    <div class="product-cell">
                                        <img src="{{ $product->primaryImageUrl() }}" alt="" class="product-thumb">
                                        <div class="product-cell-copy">
                                            <a href="{{ route('admin.products.edit', $product) }}" class="product-name">{{ $product->name }}</a>
                                            <p class="product-meta">{{ $product->sku }} · /product/{{ $product->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="product-category-name">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                    <p class="product-category-meta">{{ $product->subcategory?->name ?? 'No subcategory' }}</p>
                                </td>
                                <td class="product-price">{{ $product->currency }} {{ number_format((float) $product->base_price, 2) }}</td>
                                <td>
                                    <span class="product-status product-status--{{ $product->status === 'active' && $product->is_active ? 'active' : 'inactive' }}">{{ ucfirst($product->status) }}</span>
                                </td>
                                <td>
                                    @php($badgeLabel = trim((string) $product->badge_label))
                                    <div class="product-flags">
                                        @if($product->is_featured)
                                            <span class="product-flag product-flag--featured">Featured</span>
                                        @endif
                                        @if($badgeLabel !== '')
                                            <span class="product-flag product-flag--customizable">{{ $badgeLabel }}</span>
                                        @endif
                                        @if(! $product->is_featured && $badgeLabel === '')
                                            <span class="product-muted-placeholder">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="product-updated-cell">
                                    <p class="product-updated-date">{{ $product->updated_at?->format('M d, Y') ?? '—' }}</p>
                                    <p class="product-updated-meta">{{ $product->updated_at?->format('h:i A') ?? 'Time unavailable' }} · {{ $product->updater?->name ?: $product->updater?->email ?: 'System / import' }}</p>
                                </td>
                                <td>
                                    @php($updateSummary = trim((string) $product->last_update_summary))
                                    <p class="product-update-summary" title="{{ $updateSummary !== '' ? $updateSummary : 'No update summary was recorded for this existing product.' }}">
                                        {{ $updateSummary !== '' ? $updateSummary : 'No update summary recorded' }}
                                    </p>
                                </td>
                                <td class="product-actions-column">
                                    <div class="product-row-menu" data-product-row-menu>
                                        <button
                                            type="button"
                                            class="product-row-menu__trigger"
                                            data-product-row-menu-trigger
                                            aria-label="Actions for {{ $product->name }}"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                        >
                                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h.01M10 10h.01M16 10h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                        </button>

                                        <div class="product-row-menu__panel" data-product-row-menu-panel role="menu" hidden>
                                            <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="product-row-menu__item" role="menuitem">
                                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2.5 10s2.7-4.8 7.5-4.8 7.5 4.8 7.5 4.8-2.7 4.8-7.5 4.8S2.5 10 2.5 10Z" stroke="currentColor" stroke-width="1.6"/><circle cx="10" cy="10" r="2.2" stroke="currentColor" stroke-width="1.6"/></svg>
                                                Preview
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product) }}" class="product-row-menu__item" role="menuitem">
                                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m11.8 4.4 3.8 3.8M4.5 15.5l1.8-4.6 7.4-7.4a1.4 1.4 0 0 1 2 0l.8.8a1.4 1.4 0 0 1 0 2l-7.4 7.4-4.6 1.8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.products.duplicate', $product) }}" class="product-row-menu__form">
                                                @csrf
                                                <button class="product-row-menu__item" type="submit" role="menuitem">
                                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="6.5" y="6.5" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><path d="M13.5 6.5V5A1.5 1.5 0 0 0 12 3.5H5A1.5 1.5 0 0 0 3.5 5v7A1.5 1.5 0 0 0 5 13.5h1.5" stroke="currentColor" stroke-width="1.6"/></svg>
                                                    Duplicate
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="product-row-menu__form product-row-menu__form--danger" onsubmit="return confirm('Move this product to trash?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="product-row-menu__item product-row-menu__item--danger" type="submit" role="menuitem">
                                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.5 5.5h13M8 8.5v5M12 8.5v5M5.5 5.5l.7 10h7.6l.7-10M7.5 5.5l.6-2h3.8l.6 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="product-empty-state">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="product-table-footer">
                <div class="admin-pagination">{{ $products->links('pagination.nextplay', ['itemName' => 'product']) }}</div>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const checkAll = document.getElementById('product-check-all');
            const bulkForm = document.querySelector('[data-product-bulk-form]');
            const bulkAction = document.querySelector('[data-product-bulk-action]');
            const rowChecks = () => Array.from(document.querySelectorAll('.product-row-check'));

            checkAll?.addEventListener('change', event => {
                rowChecks().forEach(checkbox => checkbox.checked = event.target.checked);
            });

            rowChecks().forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    if (!checkAll) return;
                    const checks = rowChecks();
                    const checked = checks.filter(item => item.checked).length;
                    checkAll.checked = checks.length > 0 && checked === checks.length;
                    checkAll.indeterminate = checked > 0 && checked < checks.length;
                });
            });

            bulkForm?.addEventListener('submit', event => {
                const selectedCount = rowChecks().filter(item => item.checked).length;
                const action = bulkAction?.value || '';

                if (selectedCount === 0) {
                    event.preventDefault();
                    alert('Please select at least one product first.');
                    return;
                }

                if (action === '') {
                    event.preventDefault();
                    alert('Please choose a bulk action first.');
                    return;
                }

                const message = action === 'delete'
                    ? `Move ${selectedCount} selected product${selectedCount === 1 ? '' : 's'} to trash?`
                    : `Apply this bulk action to ${selectedCount} selected product${selectedCount === 1 ? '' : 's'}?`;

                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        })();
    </script>

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
                panel.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            };

            const closePanel = () => {
                panel.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
                updateActiveSuggestion();
            };

            const updateActiveSuggestion = () => {
                resultsBox.querySelectorAll('[data-product-suggestion-item]').forEach((item, index) => {
                    item.classList.toggle('is-active', index === activeIndex);
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
                        <div class="product-search-state">
                            No matching products found${query ? ` for “${escapeHtml(query)}”` : ''}.
                        </div>
                    `;
                    openPanel();
                    return;
                }

                resultsBox.innerHTML = suggestions.map((item, index) => `
                    <button
                        type="button"
                        class="product-search-suggestion"
                        data-product-suggestion-item
                        data-product-suggestion-index="${index}"
                    >
                        <img src="${escapeHtml(item.image_url)}" alt="" class="product-search-suggestion__image">
                        <span class="product-search-suggestion__copy">
                            <span class="product-search-suggestion__name">${escapeHtml(item.name)}</span>
                            <span class="product-search-suggestion__meta">
                                ${escapeHtml(item.sku || 'No SKU')} · ${escapeHtml(item.category || 'Uncategorized')}
                            </span>
                        </span>
                        <span class="product-search-suggestion__status">${escapeHtml(item.status || 'Draft')}</span>
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
                resultsBox.innerHTML = '<div class="product-search-state">Searching products...</div>';
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
                    resultsBox.innerHTML = '<div class="product-search-state product-search-state--error">Could not load suggestions. Please try again.</div>';
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
                if (panel.hidden) return;

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

    <script>
        (() => {
            const menus = Array.from(document.querySelectorAll('[data-product-row-menu]'));
            let activeMenu = null;

            const closeMenu = (menu) => {
                if (!menu) return;
                const trigger = menu.querySelector('[data-product-row-menu-trigger]');
                const panel = menu.querySelector('[data-product-row-menu-panel]');
                if (!trigger || !panel) return;

                panel.hidden = true;
                panel.style.removeProperty('top');
                panel.style.removeProperty('left');
                trigger.setAttribute('aria-expanded', 'false');
                menu.classList.remove('is-open');
                if (activeMenu === menu) activeMenu = null;
            };

            const closeAll = (except = null) => {
                menus.forEach(menu => {
                    if (menu !== except) closeMenu(menu);
                });
            };

            const positionMenu = (menu) => {
                const trigger = menu.querySelector('[data-product-row-menu-trigger]');
                const panel = menu.querySelector('[data-product-row-menu-panel]');
                if (!trigger || !panel) return;

                const viewportPadding = 8;
                const gap = 6;
                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();

                let left = triggerRect.right - panelRect.width;
                left = Math.max(viewportPadding, Math.min(left, window.innerWidth - panelRect.width - viewportPadding));

                let top = triggerRect.bottom + gap;
                if (top + panelRect.height > window.innerHeight - viewportPadding) {
                    top = triggerRect.top - panelRect.height - gap;
                }
                top = Math.max(viewportPadding, top);

                panel.style.left = `${Math.round(left)}px`;
                panel.style.top = `${Math.round(top)}px`;
            };

            menus.forEach(menu => {
                const trigger = menu.querySelector('[data-product-row-menu-trigger]');
                const panel = menu.querySelector('[data-product-row-menu-panel]');
                if (!trigger || !panel) return;

                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const shouldOpen = panel.hidden;
                    closeAll(menu);

                    if (!shouldOpen) {
                        closeMenu(menu);
                        return;
                    }

                    panel.hidden = false;
                    trigger.setAttribute('aria-expanded', 'true');
                    menu.classList.add('is-open');
                    activeMenu = menu;
                    positionMenu(menu);
                });

                panel.addEventListener('click', (event) => {
                    if (event.target.closest('a, button')) {
                        window.setTimeout(() => closeMenu(menu), 0);
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (activeMenu && !activeMenu.contains(event.target)) {
                    closeMenu(activeMenu);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && activeMenu) {
                    const trigger = activeMenu.querySelector('[data-product-row-menu-trigger]');
                    closeMenu(activeMenu);
                    trigger?.focus();
                }
            });

            window.addEventListener('resize', () => closeMenu(activeMenu));
            window.addEventListener('scroll', () => closeMenu(activeMenu), true);
        })();
    </script>
</x-layouts.admin>
