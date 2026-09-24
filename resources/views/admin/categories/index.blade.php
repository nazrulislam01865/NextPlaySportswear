<x-layouts.admin title="Categories" :compact-header="true">
    @php
        $categoryStatuses = ['draft', 'active', 'inactive', 'archived'];
        $categoryTypes = ['standard', 'sport', 'collection', 'apparel', 'accessory', 'promotional', 'sale', 'new-arrival', 'navigation-only'];

        $hasActiveFilters = filled($filters['q'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['type'] ?? null)
            || (bool) ($filters['empty'] ?? false);

        $stats = [
            ['label' => 'Total Categories', 'value' => $analytics['total'], 'note' => 'All catalog nodes', 'icon' => 'tree', 'tone' => 'slate'],
            ['label' => 'Active', 'value' => $analytics['active'], 'note' => 'Published and available', 'icon' => 'check', 'tone' => 'blue'],
            ['label' => 'Featured', 'value' => $analytics['featured'], 'note' => 'Homepage parent categories', 'icon' => 'star', 'tone' => 'slate'],
            ['label' => 'Empty', 'value' => $analytics['empty'], 'note' => 'No products in category subtree', 'icon' => 'folder', 'tone' => 'amber'],
            ['label' => 'Tree Depth', 'value' => $analytics['max_depth'], 'note' => 'Maximum current level', 'icon' => 'chart', 'tone' => 'violet'],
        ];
    @endphp

    <div class="category-admin-page">
        <section class="category-page-toolbar" aria-label="Category actions">
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
                        @if(auth('admin')->user()?->canAdmin('categories.manage'))
                            <a href="#catalog-page-banner">All Products Banner</a>
                        @endif
                        <a href="{{ route('admin.categories.export') }}">Export CSV</a>
                        <a href="{{ route('admin.attributes.index') }}">Catalog Attributes</a>
                        <a href="{{ route('admin.menus.index') }}">Navigation Menus</a>
                    </div>
                </details>
            </div>
        </section>

        @if(auth('admin')->user()?->canAdmin('categories.manage'))
            <section id="catalog-page-banner" class="scroll-mt-24 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="catalog-page-banner-title">
                <form
                    method="POST"
                    action="{{ route('admin.categories.catalog-banner.update') }}"
                    enctype="multipart/form-data"
                    class="grid gap-6 xl:grid-cols-[minmax(0,.95fr)_minmax(0,1.05fr)]"
                    x-data="{
                        catalogBannerColor: @js((string) old('products_banner_color', $catalogBanner['color'] ?? '')),
                        catalogBannerPreview: @js($catalogBanner['image'] ?? null),
                        previewCatalogBanner(event) {
                            const file = event.target.files && event.target.files[0];
                            if (!file) return;
                            if (this.catalogBannerPreview && this.catalogBannerPreview.startsWith('blob:')) {
                                URL.revokeObjectURL(this.catalogBannerPreview);
                            }
                            this.catalogBannerPreview = URL.createObjectURL(file);
                        }
                    }"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Storefront catalog</p>
                        <h2 id="catalog-page-banner-title" class="mt-2 text-2xl font-black text-brand-ink">All Products Page Banner</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Upload a banner image, choose a background color, or use both. When an image is present it is displayed over the selected color.</p>

                        <div
                            class="relative mt-5 grid min-h-48 place-items-center overflow-hidden rounded-2xl border border-slate-200 text-white"
                            x-bind:style="catalogBannerColor ? 'background-color: ' + catalogBannerColor : 'background: linear-gradient(135deg, #15345d 0%, #0d2545 58%, #071a31 100%)'"
                        >
                            <template x-if="catalogBannerPreview">
                                <img :src="catalogBannerPreview" alt="All Products banner preview" class="absolute inset-0 h-full w-full object-cover">
                            </template>
                            <template x-if="!catalogBannerPreview">
                                <span class="relative z-10 px-6 text-center text-sm font-black">Banner color preview</span>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <label class="admin-label">
                            Upload banner image <span class="font-normal text-slate-400">(optional)</span>
                            <input
                                class="admin-input py-3"
                                type="file"
                                name="products_banner_file"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                                x-on:change="previewCatalogBanner($event)"
                            >
                            <small class="font-normal text-slate-500">JPG, PNG, WebP, or AVIF. Maximum 8 MB. A wide image is recommended.</small>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_74px] sm:items-end">
                            <label class="admin-label">
                                Banner background color <span class="font-normal text-slate-400">(optional)</span>
                                <input
                                    class="admin-input font-mono"
                                    name="products_banner_color"
                                    x-model="catalogBannerColor"
                                    value="{{ old('products_banner_color', $catalogBanner['color'] ?? '') }}"
                                    maxlength="7"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    placeholder="#15345d"
                                    autocomplete="off"
                                >
                                <small class="font-normal text-slate-500">Leave blank to keep the existing NextPlay gradient when no image is uploaded.</small>
                            </label>
                            <label class="admin-label">
                                Pick
                                <input
                                    class="h-[46px] w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1"
                                    type="color"
                                    x-bind:value="catalogBannerColor || '#15345d'"
                                    x-on:input="catalogBannerColor = $event.target.value"
                                    aria-label="Choose All Products banner color"
                                >
                            </label>
                        </div>

                        @if(filled($catalogBanner['image'] ?? null))
                            <label class="flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm">
                                <input type="hidden" name="remove_products_banner" value="0">
                                <input class="mt-1" type="checkbox" name="remove_products_banner" value="1">
                                <span>
                                    <strong class="block text-red-800">Remove current banner image</strong>
                                    <small class="mt-1 block leading-5 text-red-700">The selected color will remain available after the image is removed.</small>
                                </span>
                            </label>
                        @endif

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-red">Save All Products Banner</button>
                        </div>
                    </div>
                </form>
            </section>
        @endif

        <section class="category-stats-grid" aria-label="Category summary">
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
                        <span class="category-filter-leading-icon" aria-hidden="true">
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
                        <span class="category-filter-select-icon" aria-hidden="true">
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
                        <span class="category-filter-select-icon" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                    </label>

                    <label class="category-empty-filter">
                        <input type="checkbox" name="empty" value="1" @checked($filters['empty'] ?? false)>
                        <span>Empty only</span>
                    </label>

                    <div class="category-filter-actions">
                        <button class="category-filter-button" type="submit">
                            Filter
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('admin.categories.index') }}" class="category-filter-clear">Clear</a>
                        @endif
                    </div>
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

                    <button class="category-import-button category-import-button--compact" type="submit">
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
                <option value="feature">Mark featured parent category</option>
                <option value="unfeature">Remove featured</option>
                <option value="show_in_menu">Show in menu</option>
                <option value="hide_from_menu">Hide from menu</option>
            </select>

            <button
                class="category-bulk-button category-bulk-button--compact"
                type="submit"
                onclick="return confirm('Apply this action to the selected categories?')"
            >
                Apply
            </button>

            <span class="category-bulk-note">
                Only parent categories can be marked featured for the homepage section.
            </span>
        </form>

        <section class="category-table-card">
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
                            @php
                                $deleteImpact = $categoryDeleteImpacts[(int) $category->id] ?? [
                                    'category_count' => 1,
                                    'child_category_count' => 0,
                                    'category_names' => [],
                                    'product_count' => 0,
                                    'product_names' => [],
                                    'menu_item_count' => 0,
                                ];
                            @endphp
                            <tr>
                                <td class="px-6 py-5" data-label="Select">
                                    <input class="category-row-check" type="checkbox" name="category_ids[]" value="{{ $category->id }}" form="bulk-category-form" aria-label="Select {{ $category->name }}">
                                </td>

                                <td class="px-6 py-5" data-label="Category Tree" data-card-cell="category">
                                    <div class="category-tree-cell" style="--category-depth: {{ min($category->depth, 6) }}">
                                        @if($category->depth > 0)
                                            <span class="category-branch-mark" aria-hidden="true">↳</span>
                                        @endif
                                        @if($category->parent_id === null)
                                            <span class="category-folder-icon" aria-hidden="true">
                                                <img src="{{ $category->iconUrl() }}" alt="" loading="lazy">
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                <strong class="category-name truncate">{{ $category->name }}</strong>
                                                @if($category->is_featured)
                                                    <span class="category-featured-pill">Featured</span>
                                                @endif
                                            </div>
                                            <p class="category-slug">/category/{{ $category->slug }}</p>
                                            @if($category->parent)
                                                <p class="category-parent">Parent: {{ $category->parent->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5" data-label="Type / Template">
                                    <strong class="category-type">{{ ucwords(str_replace('-', ' ', $category->category_type)) }}</strong>
                                    <span class="category-template">{{ ucwords(str_replace('_', ' ', $category->page_template)) }}</span>
                                </td>

                                <td class="px-6 py-5 text-center" data-label="Products">
                                    <a
                                        href="{{ route('admin.categories.products.index', $category) }}"
                                        class="category-products-count-link"
                                        aria-label="View {{ number_format($category->products_count) }} {{ \Illuminate\Support\Str::plural('product', (int) $category->products_count) }} in the {{ $category->name }} category subtree"
                                        title="{{ $category->children_count > 0 ? 'Includes unique products from every descendant leaf category.' : 'View products assigned to this leaf category.' }}"
                                    >
                                        <span>{{ number_format($category->products_count) }} {{ \Illuminate\Support\Str::plural('product', (int) $category->products_count) }}</span>
                                        <small aria-hidden="true">›</small>
                                    </a>
                                </td>
                                <td class="category-children-count" data-label="Children">{{ number_format($category->children_count) }}</td>

                                <td class="px-6 py-5" data-label="Visibility">
                                    <span class="category-status-pill category-status-pill--{{ $category->status }}">{{ ucfirst($category->status) }}</span>
                                    <p class="category-visibility-meta">Catalog {{ $category->is_visible_in_catalog ? 'on' : 'off' }} · Menu {{ $category->is_visible_in_menu ? 'on' : 'off' }}</p>
                                </td>

                                <td class="category-updated-cell" data-label="Updated">
                                    <span>{{ $category->updated_at?->format('M j, Y g:i A') }}</span>
                                    @if($category->updater)
                                        <small>by {{ $category->updater->name }}</small>
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
                                                <button
                                                    type="button"
                                                    class="category-row-danger-action"
                                                    data-category-delete-trigger
                                                    data-delete-url="{{ route('admin.categories.destroy', $category) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    data-category-count="{{ (int) $deleteImpact['category_count'] }}"
                                                    data-child-count="{{ (int) $deleteImpact['child_category_count'] }}"
                                                    data-product-count="{{ (int) $deleteImpact['product_count'] }}"
                                                    data-menu-count="{{ (int) $deleteImpact['menu_item_count'] }}"
                                                    data-category-names="{{ json_encode($deleteImpact['category_names'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                    data-product-names="{{ json_encode($deleteImpact['product_names'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-8 py-16 text-center">
                                    <p class="category-empty-title">No categories found</p>
                                    <p class="category-empty-copy">Try another keyword, status, type, or empty-category filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="category-table-footer">
                <div class="admin-pagination">{{ $categories->links('pagination.nextplay', ['itemName' => 'category']) }}</div>
            </div>
        </section>
    </div>

    <dialog id="category-delete-dialog" class="category-delete-dialog">
        <div class="category-delete-panel">
            <div class="category-delete-header">
                <div>
                    <p class="category-delete-eyebrow">Category-tree deletion</p>
                    <h2 class="category-delete-title">Delete <span data-category-delete-name></span>?</h2>
                    <p class="category-delete-intro">The selected category and every child category below it will be deleted together.</p>
                </div>
                <button type="button" class="category-delete-close-button" aria-label="Close delete dialog" data-category-delete-close>×</button>
            </div>

            <div class="category-delete-body">
                <div class="category-delete-warning">
                    Affected products will not be deleted. They will become completely categoryless, with all of their category assignments removed. Menu links pointing to deleted categories will be disabled.
                </div>

                <div class="category-delete-metrics">
                    <div class="category-delete-metric">
                        <strong data-category-delete-total-categories>0</strong>
                        <span>Categories deleted</span>
                    </div>
                    <div class="category-delete-metric">
                        <strong data-category-delete-products>0</strong>
                        <span>Products categoryless</span>
                    </div>
                    <div class="category-delete-metric">
                        <strong data-category-delete-menus>0</strong>
                        <span>Menu links disabled</span>
                    </div>
                </div>

                <div class="category-delete-detail-grid">
                    <section class="category-delete-detail-card" data-category-delete-children-section>
                        <h3>Child categories affected</h3>
                        <ul data-category-delete-children></ul>
                    </section>
                    <section class="category-delete-detail-card" data-category-delete-products-section>
                        <h3>Products affected</h3>
                        <ul data-category-delete-product-names></ul>
                    </section>
                </div>
            </div>

            <form method="POST" class="category-delete-footer" data-category-delete-form>
                @csrf
                @method('DELETE')
                <button type="button" class="category-delete-cancel-button" data-category-delete-close>Cancel</button>
                <button type="submit" class="category-delete-confirm-button">Delete affected category tree</button>
            </form>
        </div>
    </dialog>

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

            const categoryDeleteDialog = document.getElementById('category-delete-dialog');
            const categoryDeleteForm = categoryDeleteDialog?.querySelector('[data-category-delete-form]');

            const fillDeleteList = (list, values, emptyMessage, extraCount = 0) => {
                if (!list) return;

                list.replaceChildren();
                const items = Array.isArray(values) && values.length ? values : [emptyMessage];

                items.forEach(value => {
                    const item = document.createElement('li');
                    item.textContent = value;
                    list.appendChild(item);
                });

                if (extraCount > 0) {
                    const item = document.createElement('li');
                    item.textContent = `+ ${extraCount} more`;
                    item.className = 'category-delete-more-item';
                    list.appendChild(item);
                }
            };

            document.querySelectorAll('[data-category-delete-trigger]').forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const categoryCount = Number(trigger.dataset.categoryCount || 1);
                    const childCount = Number(trigger.dataset.childCount || 0);
                    const productCount = Number(trigger.dataset.productCount || 0);
                    const menuCount = Number(trigger.dataset.menuCount || 0);
                    let categoryNames = [];
                    let productNames = [];

                    try { categoryNames = JSON.parse(trigger.dataset.categoryNames || '[]'); } catch (error) { categoryNames = []; }
                    try { productNames = JSON.parse(trigger.dataset.productNames || '[]'); } catch (error) { productNames = []; }

                    if (categoryDeleteForm) categoryDeleteForm.action = trigger.dataset.deleteUrl || '';
                    categoryDeleteDialog?.querySelector('[data-category-delete-name]')?.replaceChildren(document.createTextNode(trigger.dataset.categoryName || 'this category'));
                    categoryDeleteDialog?.querySelector('[data-category-delete-total-categories]')?.replaceChildren(document.createTextNode(String(categoryCount)));
                    categoryDeleteDialog?.querySelector('[data-category-delete-products]')?.replaceChildren(document.createTextNode(String(productCount)));
                    categoryDeleteDialog?.querySelector('[data-category-delete-menus]')?.replaceChildren(document.createTextNode(String(menuCount)));

                    fillDeleteList(
                        categoryDeleteDialog?.querySelector('[data-category-delete-children]'),
                        categoryNames,
                        childCount > 0 ? 'Child category details unavailable.' : 'No child categories; only the selected category will be deleted.',
                        Math.max(0, childCount - categoryNames.length)
                    );
                    fillDeleteList(
                        categoryDeleteDialog?.querySelector('[data-category-delete-product-names]'),
                        productNames,
                        productCount > 0 ? 'Product details unavailable.' : 'No products will be affected.',
                        Math.max(0, productCount - productNames.length)
                    );

                    trigger.closest('details')?.removeAttribute('open');

                    if (typeof categoryDeleteDialog?.showModal === 'function') {
                        categoryDeleteDialog.showModal();
                    } else if (confirm(`Delete ${trigger.dataset.categoryName || 'this category'} and all affected child categories?`)) {
                        categoryDeleteForm?.requestSubmit();
                    }
                });
            });

            categoryDeleteDialog?.querySelectorAll('[data-category-delete-close]').forEach(button => {
                button.addEventListener('click', () => categoryDeleteDialog.close());
            });

            categoryDeleteDialog?.addEventListener('click', event => {
                if (event.target === categoryDeleteDialog) categoryDeleteDialog.close();
            });
        </script>
    @endonce
</x-layouts.admin>
