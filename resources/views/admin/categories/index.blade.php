<x-layouts.admin title="Dynamic Categories">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Catalog architecture</p>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Manage a secure multi-level category tree, storefront visibility, category-specific facets, SEO, landing content, product assignments, menus, imports, and ordering without changing storefront templates.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.categories.ordering') }}" class="btn btn-white">Reorder Tree</a>
            <a href="{{ route('admin.categories.export') }}" class="btn btn-white">Export CSV</a>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-white">Catalog Attributes</a>
            <a href="{{ route('admin.menus.index') }}" class="btn btn-white">Navigation Menus</a>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-red">Add Category</a>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['label'=>'Total categories','value'=>$analytics['total'],'note'=>'All catalog nodes'],
            ['label'=>'Active','value'=>$analytics['active'],'note'=>'Published and available'],
            ['label'=>'Featured','value'=>$analytics['featured'],'note'=>'Homepage/category promotion'],
            ['label'=>'Empty','value'=>$analytics['empty'],'note'=>'No direct product assignment'],
            ['label'=>'Tree depth','value'=>$analytics['max_depth'],'note'=>'Maximum current level'],
        ] as $stat)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <p class="text-[10px] font-black uppercase tracking-[.13em] text-slate-500">{{ $stat['label'] }}</p>
                <strong class="mt-2 block text-3xl text-brand-ink">{{ number_format($stat['value']) }}</strong>
                <span class="mt-1 block text-xs text-slate-500">{{ $stat['note'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="mb-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card md:grid-cols-[minmax(0,1fr)_170px_170px_auto_auto]">
            <input class="admin-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, slug, or menu label">
            <select class="admin-input" name="status">
                <option value="">All statuses</option>
                @foreach(['draft','active','inactive','archived'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach
            </select>
            <select class="admin-input" name="type">
                <option value="">All types</option>
                @foreach(['standard','sport','collection','apparel','accessory','promotional','sale','new-arrival','navigation-only'] as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucwords(str_replace('-',' ',$type)) }}</option>@endforeach
            </select>
            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-bold"><input type="checkbox" name="empty" value="1" @checked($filters['empty'] ?? false)> Empty only</label>
            <button class="btn btn-navy">Filter</button>
        </form>

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.categories.import') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:flex-row sm:items-end">
            @csrf
            <label class="admin-label min-w-0 flex-1">Import category CSV
                <input class="admin-input" type="file" name="category_csv" accept=".csv,text/csv" required>
            </label>
            <button class="btn btn-white shrink-0">Import</button>
        </form>
    </div>

    <form id="bulk-category-form" method="POST" action="{{ route('admin.categories.bulk') }}" class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:flex-row sm:items-center">
        @csrf
        <strong class="text-sm text-brand-ink">Selected categories</strong>
        <select class="admin-input sm:max-w-xs" name="action" required>
            <option value="">Choose bulk action</option>
            <option value="activate">Activate</option>
            <option value="deactivate">Deactivate</option>
            <option value="archive">Archive</option>
            <option value="feature">Mark featured</option>
            <option value="unfeature">Remove featured</option>
            <option value="show_in_menu">Show in menu</option>
            <option value="hide_from_menu">Hide from menu</option>
        </select>
        <button class="btn btn-navy" onclick="return confirm('Apply this action to the selected categories?')">Apply</button>
        <span class="text-xs text-slate-500">Bulk changes are validated and recorded server-side.</span>
    </form>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="min-w-[1220px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.13em] text-slate-500">
                    <tr>
                        <th class="w-12 px-4 py-4"><input id="category-check-all" type="checkbox" aria-label="Select all categories on this page"></th>
                        <th class="px-5 py-4">Category tree</th><th class="px-5 py-4">Type / Template</th><th class="px-5 py-4">Products</th><th class="px-5 py-4">Children</th><th class="px-5 py-4">Visibility</th><th class="px-5 py-4">Updated</th><th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-4"><input class="category-row-check" type="checkbox" name="category_ids[]" value="{{ $category->id }}" form="bulk-category-form" aria-label="Select {{ $category->name }}"></td>
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3" style="padding-left: {{ min($category->depth, 6) * 18 }}px">
                                    <span class="mt-1 text-slate-300">{{ $category->depth ? '↳' : '▦' }}</span>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2"><strong class="truncate text-brand-ink">{{ $category->name }}</strong>@if($category->is_featured)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700">Featured</span>@endif</div>
                                        <p class="mt-1 font-mono text-[11px] text-slate-500">/category/{{ $category->slug }}</p>
                                        @if($category->parent)<p class="mt-1 text-xs text-slate-400">Parent: {{ $category->parent->name }}</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600"><strong class="block text-slate-800">{{ ucwords(str_replace('-',' ',$category->category_type)) }}</strong><span>{{ ucwords(str_replace('_',' ',$category->page_template)) }}</span></td>
                            <td class="px-5 py-4 font-black">{{ $category->products_count }}</td>
                            <td class="px-5 py-4">{{ $category->children_count }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $category->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($category->status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($category->status) }}</span>
                                <p class="mt-2 text-[11px] text-slate-500">Catalog: {{ $category->is_visible_in_catalog ? 'Yes' : 'No' }} · Menu: {{ $category->is_visible_in_menu ? 'Yes' : 'No' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">{{ $category->updated_at?->format('M j, Y g:i A') }}@if($category->updater)<span class="mt-1 block">by {{ $category->updater->name }}</span>@endif</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if($category->status === 'active')<a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Preview</a>@endif
                                    <a href="{{ route('admin.categories.create', ['parent_id'=>$category->id]) }}" class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Add Child</a>
                                    <a href="{{ route('admin.categories.products.index', $category) }}" class="rounded-lg border border-blue-200 px-3 py-2 font-bold text-brand-blue">Products</a>
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Edit</a>
                                    <form method="POST" action="{{ route('admin.categories.duplicate', $category) }}" onsubmit="return confirm('Duplicate this category as a draft?')">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 font-bold">Duplicate</button></form>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Move this category to trash?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 font-bold text-red-700">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No categories match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $categories->links() }}</div>
    </section>

    @once
        <script>
            document.getElementById('category-check-all')?.addEventListener('change', event => {
                document.querySelectorAll('.category-row-check').forEach(checkbox => checkbox.checked = event.target.checked);
            });
        </script>
    @endonce
</x-layouts.admin>
