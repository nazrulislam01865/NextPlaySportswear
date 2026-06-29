<x-layouts.admin title="Dynamic Categories">
    @php
        $categoryStatuses = ['draft', 'active', 'inactive', 'archived'];
        $categoryTypes = ['standard', 'sport', 'collection', 'apparel', 'accessory', 'promotional', 'sale', 'new-arrival', 'navigation-only'];
        $from = $categories->firstItem();
        $to = $categories->lastItem();
        $total = $categories->total();

        $stats = [
            ['label' => 'Total Categories', 'value' => $analytics['total'], 'note' => 'All catalog nodes', 'icon' => 'tree', 'tone' => 'slate'],
            ['label' => 'Active', 'value' => $analytics['active'], 'note' => 'Published and available', 'icon' => 'check', 'tone' => 'blue'],
            ['label' => 'Featured', 'value' => $analytics['featured'], 'note' => 'Homepage/category promotion', 'icon' => 'star', 'tone' => 'slate'],
            ['label' => 'Empty', 'value' => $analytics['empty'], 'note' => 'No direct product assignment', 'icon' => 'folder', 'tone' => 'amber'],
            ['label' => 'Tree Depth', 'value' => $analytics['max_depth'], 'note' => 'Maximum current level', 'icon' => 'chart', 'tone' => 'violet'],
        ];
    @endphp

    <div class="category-admin-page space-y-6">
        <section class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Catalog Architecture</p>
                <p class="mt-3 text-base font-medium leading-8 text-slate-600 sm:text-lg">
                    Manage a secure multi-level category tree, storefront visibility, category-specific facets,
                    SEO, landing content, product assignments, menus, imports, and ordering without
                    changing storefront templates.
                </p>
            </div>

            <div class="category-top-actions">
                <a href="{{ route('admin.categories.ordering') }}" class="category-header-action category-header-action-light">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 6h14M5 12h14M5 18h9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                        <path d="M7.5 9v2.8M7.5 15v2.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                    </svg>
                    Reorder Tree
                </a>

                <a href="{{ route('admin.categories.create') }}" class="category-header-action category-header-action-primary">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                    </svg>
                    Add Category
                </a>

                <details class="category-tools-menu">
                    <summary aria-label="Open category tools">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5.5h.01M12 12h.01M12 18.5h.01" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" />
                        </svg>
                    </summary>
                    <div class="category-tools-panel">
                        <a href="{{ route('admin.categories.export') }}">Export CSV</a>
                        <a href="{{ route('admin.attributes.index') }}">Catalog Attributes</a>
                        <a href="{{ route('admin.menus.index') }}">Navigation Menus</a>
                    </div>
                </details>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($stats as $stat)
                <article class="category-stat-card">
                    <span class="category-stat-icon category-stat-icon-{{ $stat['tone'] }}" aria-hidden="true">
                        @switch($stat['icon'])
                            @case('tree')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 4v5m0 0H7.5A2.5 2.5 0 0 0 5 11.5V14m7-5h4.5A2.5 2.5 0 0 1 19 11.5V14M5 14v2.5h5V14H5Zm9 0v2.5h5V14h-5Zm-4-10v3h4V4h-4Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" /></svg>
                                @break
                            @case('check')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.9" /><path d="m8.5 12.3 2.3 2.3 4.8-5.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                @break
                            @case('star')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m12 3.5 2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.2-4.1 5.8-.8L12 3.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" /></svg>
                                @break
                            @case('folder')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M3.5 7.5a2 2 0 0 1 2-2h4.4l2 2H18.5a2 2 0 0 1 2 2v7.7a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2V7.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" /></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none"><path d="M6 19V12m6 7V5m6 14v-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" /></svg>
                        @endswitch
                    </span>
                    <div class="min-w-0">
                        <p class="category-stat-label">{{ $stat['label'] }}</p>
                        <strong>{{ number_format($stat['value']) }}</strong>
                        <span>{{ $stat['note'] }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="category-filter-card" aria-label="Category filters and import tools">
            <div class="category-filter-shell">
                <form method="GET" class="category-filter-form">
                    <label class="category-field relative block">
                        <span class="sr-only">Search categories</span>
                        <span class="pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                <path d="m21 21-4.3-4.3M10.8 18.2a7.4 7.4 0 1 1 0-14.8 7.4 7.4 0 0 1 0 14.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input
                            class="admin-input category-control pl-14"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Search categories"
                            autocomplete="off"
                            maxlength="100"
                        >
                    </label>

                    <label class="category-field relative block">
                        <span class="sr-only">Category status</span>
                        <select class="admin-input category-control appearance-none pr-12" name="status">
                            <option value="">All statuses</option>
                            @foreach($categoryStatuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                    </label>

                    <label class="category-field relative block">
                        <span class="sr-only">Category type</span>
                        <select class="admin-input category-control appearance-none pr-12" name="type">
                            <option value="">All types</option>
                            @foreach($categoryTypes as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>
                                    {{ ucwords(str_replace('-', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-slate-500" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                    </label>

                    <label class="category-empty-filter">
                        <input type="checkbox" name="empty" value="1" @checked($filters['empty'] ?? false)>
                        <span>Empty only</span>
                    </label>

                    <button class="category-filter-button" type="submit">
                        Filter
                    </button>
                </form>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('admin.categories.import') }}"
                    class="category-import-form"
                >
                    @csrf

                    <div class="category-import-field">
                        <label class="category-import-label" for="category-csv-import">
                            Import category CSV
                        </label>

                        <label class="category-file-picker" for="category-csv-import">
                            <input
                                id="category-csv-import"
                                class="category-file-input"
                                type="file"
                                name="category_csv"
                                accept=".csv,text/csv"
                                required
                                data-category-file-input
                            >
                            <span class="category-file-trigger">Choose file</span>
                            <span class="category-file-name" data-category-file-name>No file chosen</span>
                        </label>
                    </div>

                    <button class="category-import-button" type="submit">
                        Import
                    </button>
                </form>
            </div>
        </section>

        <form id="bulk-category-form" method="POST" action="{{ route('admin.categories.bulk') }}" class="category-bulk-card">
            @csrf

            <strong>Selected categories</strong>

            <select class="admin-input category-control" name="action" required>
                <option value="">Choose bulk action</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="archive">Archive</option>
                <option value="feature">Mark featured</option>
                <option value="unfeature">Remove featured</option>
                <option value="show_in_menu">Show in menu</option>
                <option value="hide_from_menu">Hide from menu</option>
            </select>

            <button
                class="category-bulk-button"
                type="submit"
                onclick="return confirm('Apply this action to the selected categories?')"
            >
                Apply
            </button>

            <span class="category-bulk-note">
                Bulk changes are validated and recorded server-side.
            </span>
        </form>

        <section class="rounded-[18px] border border-slate-200 bg-white shadow-card">
            <div class="category-management-scroll" tabindex="0" aria-label="Category tree table">
                <table class="category-management-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="w-14 px-6 py-5"><input id="category-check-all" type="checkbox" aria-label="Select all categories on this page"></th>
                            <th class="w-[330px] px-6 py-5">Category Tree</th>
                            <th class="w-[170px] px-6 py-5">Type / Template</th>
                            <th class="w-[105px] px-6 py-5 text-center">Products</th>
                            <th class="w-[105px] px-6 py-5 text-center">Children</th>
                            <th class="w-[210px] px-6 py-5">Visibility</th>
                            <th class="w-[170px] px-6 py-5">Updated</th>
                            <th class="w-[360px] px-6 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                            <tr>
                                <td class="px-6 py-5" data-label="Select">
                                    <input class="category-row-check" type="checkbox" name="category_ids[]" value="{{ $category->id }}" form="bulk-category-form" aria-label="Select {{ $category->name }}">
                                </td>

                                <td class="px-6 py-5" data-label="Category Tree" data-card-cell="category">
                                    <div class="category-tree-cell" style="--category-depth: {{ min($category->depth, 6) }}">
                                        @if($category->depth > 0)
                                            <span class="category-branch-mark" aria-hidden="true">↳</span>
                                        @endif
                                        <span class="category-folder-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M3.5 7.5a2 2 0 0 1 2-2h4.2l1.9 2H18.5a2 2 0 0 1 2 2v7.2a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2V7.5Z" fill="currentColor" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                <strong class="truncate text-brand-ink">{{ $category->name }}</strong>
                                                @if($category->is_featured)
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-[.08em] text-amber-700">Featured</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 font-mono text-xs font-medium text-slate-500">/category/{{ $category->slug }}</p>
                                            @if($category->parent)
                                                <p class="mt-1 text-xs font-medium text-slate-400">Parent: {{ $category->parent->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5" data-label="Type / Template">
                                    <strong class="block text-sm font-semibold text-slate-800">{{ ucwords(str_replace('-', ' ', $category->category_type)) }}</strong>
                                    <span class="mt-1 block text-sm text-slate-500">{{ ucwords(str_replace('_', ' ', $category->page_template)) }}</span>
                                </td>

                                <td class="px-6 py-5 text-center" data-label="Products">
                                    <a
                                        href="{{ route('admin.categories.products.index', $category) }}"
                                        class="category-products-count-link"
                                        aria-label="Manage product assignments for {{ $category->name }}"
                                    >
                                        <span>{{ number_format($category->products_count) }}</span>
                                        <small>Manage</small>
                                    </a>
                                </td>
                                <td class="px-6 py-5 text-center text-base font-semibold text-brand-ink" data-label="Children">{{ number_format($category->children_count) }}</td>

                                <td class="px-6 py-5" data-label="Visibility">
                                    <span class="admin-status-pill px-3 py-1.5 text-xs font-black {{ $category->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($category->status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($category->status) }}</span>
                                    <p class="mt-2 text-xs font-medium text-slate-500">Catalog: {{ $category->is_visible_in_catalog ? 'Yes' : 'No' }} · Menu: {{ $category->is_visible_in_menu ? 'Yes' : 'No' }}</p>
                                </td>

                                <td class="px-6 py-5 text-sm text-slate-500" data-label="Updated">
                                    <span class="whitespace-nowrap">{{ $category->updated_at?->format('M j, Y g:i A') }}</span>
                                    @if($category->updater)
                                        <span class="mt-1 block text-xs">by {{ $category->updater->name }}</span>
                                    @endif
                                </td>

                                <td class="px-6 py-5" data-label="Actions">
                                    <div class="category-item-actions">
                                        @if($category->status === 'active')
                                            <a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener" class="category-row-primary-action">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2.5 10s2.8-5 7.5-5 7.5 5 7.5 5-2.8 5-7.5 5-7.5-5-7.5-5Z" stroke="currentColor" stroke-width="1.7" /><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="1.7" /></svg>
                                                Preview
                                            </a>
                                        @else
                                            <span class="category-row-primary-action opacity-50" aria-disabled="true">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2.5 10s2.8-5 7.5-5 7.5 5 7.5 5-2.8 5-7.5 5-7.5-5-7.5-5Z" stroke="currentColor" stroke-width="1.7" /><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="1.7" /></svg>
                                                Preview
                                            </span>
                                        @endif

                                        <a href="{{ route('admin.categories.products.index', $category) }}" class="category-row-primary-action">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 5.5h12v9H4v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M7 8h6M7 11h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                                            Products
                                        </a>

                                        <a href="{{ route('admin.categories.edit', $category) }}" class="category-row-primary-action">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 13.8V16h2.2L15 7.2 12.8 5 4 13.8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="m11.7 6.1 2.2 2.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                                            Edit
                                        </a>

                                        <details class="category-row-menu">
                                            <summary aria-label="More actions for {{ $category->name }}">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4.5h.01M10 10h.01M10 15.5h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                                            </summary>
                                            <div class="category-row-menu-panel">
                                                <a href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}">Add Child</a>
                                                <a href="{{ route('admin.categories.products.index', $category) }}">Products</a>
                                                <form method="POST" action="{{ route('admin.categories.duplicate', $category) }}" onsubmit="return confirm('Duplicate this category as a draft?')">
                                                    @csrf
                                                    <button type="submit">Duplicate</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Move this category to trash?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-700">Delete</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-8 py-16 text-center">
                                    <p class="text-lg font-black text-brand-ink">No categories found</p>
                                    <p class="mt-2 text-sm font-medium text-slate-500">Try another keyword, status, type, or empty-category filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="category-table-footer">
                <p>
                    @if($total > 0)
                        Showing {{ $from }}–{{ $to }} of {{ $total }} {{ \Illuminate\Support\Str::plural('category', $total) }}
                    @else
                        Showing 0 categories
                    @endif
                </p>
                <div class="admin-pagination">{{ $categories->onEachSide(1)->links() }}</div>
            </div>
        </section>
    </div>

    @once
        <script>
            document.getElementById('category-check-all')?.addEventListener('change', event => {
                document.querySelectorAll('.category-row-check').forEach(checkbox => checkbox.checked = event.target.checked);
            });

            document.querySelectorAll('[data-category-file-input]').forEach(input => {
                input.addEventListener('change', () => {
                    const name = input.files?.[0]?.name || 'No file chosen';
                    input.closest('.category-file-picker')?.querySelector('[data-category-file-name]')?.replaceChildren(document.createTextNode(name));
                });
            });
        </script>
    @endonce
</x-layouts.admin>
