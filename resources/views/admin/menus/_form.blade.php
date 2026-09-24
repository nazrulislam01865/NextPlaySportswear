@php
    $isEdit = $menu->exists;
    $existing = $menu->allItems ?? collect();
    $selectedLocation = old('location', $menu->location);
    $isHeaderMenu = $selectedLocation === 'header-primary';

    // Shop Products is intentionally driven by the live Category Manager tree.
    // Do not show its generated descendants in the header editor; top-level menu
    // configuration remains concise and saving removes stale seeded child rows.
    $editableExisting = $existing;
    if ($isHeaderMenu && ! old('items')) {
        $shopRootIds = $existing->filter(function ($item) {
            $label = str($item->label ?? '')->lower()->replace(['-', '_'], ' ')->squish()->toString();
            return ($item->link_type === 'route' && $item->route_name === 'categories.index')
                || in_array($label, ['shop products', 'shop categories', 'categories'], true);
        })->pluck('id')->values();

        if ($shopRootIds->isNotEmpty()) {
            $descendantIds = collect();
            $frontier = $shopRootIds;
            while ($frontier->isNotEmpty()) {
                $children = $existing->whereIn('parent_id', $frontier)->pluck('id');
                if ($children->isEmpty()) {
                    break;
                }
                $descendantIds = $descendantIds->merge($children);
                $frontier = $children;
            }
            $editableExisting = $existing->reject(fn ($item) => $descendantIds->contains($item->id))->values();
        }
    }

    $initial = old('items', $editableExisting->map(fn ($item) => [
        'key' => 'item-'.$item->id,
        'parent_key' => $item->parent_id ? 'item-'.$item->parent_id : '',
        'label' => $item->label,
        'link_type' => $item->link_type,
        'category_id' => $item->category_id,
        'route_name' => $item->route_name,
        'url' => $item->url,
        'target' => $item->target,
        'css_class' => $item->css_class,
        'is_active' => $item->is_active,
        'sort_order' => $item->sort_order,
    ])->values()->all());
@endphp

<div
    class="space-y-6"
    x-data="menuAdminForm(@js($initial), @js($selectedLocation))"
>
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-extrabold">Please fix the highlighted menu settings.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-admin.section-card
        title="Menu Settings"
        description="Choose where this menu appears. The primary header menu controls both desktop and mobile navigation."
    >
        <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr_auto] lg:items-end">
            <label class="admin-label">
                Menu name
                <input
                    class="admin-input"
                    name="name"
                    value="{{ old('name', $menu->name) }}"
                    placeholder="Primary Header"
                    required
                >
            </label>

            <label class="admin-label">
                Display location
                <select class="admin-input" name="location" x-model="location">
                    <option value="">No automatic location</option>
                    @foreach($locationOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedLocation === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="flex min-h-[46px] items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))>
                <span>
                    <span class="block text-sm font-extrabold text-slate-900">Menu active</span>
                    <span class="block text-xs font-normal text-slate-500">Show this menu on the storefront</span>
                </span>
            </label>
        </div>

        <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <summary class="cursor-pointer text-sm font-bold text-slate-700">Advanced menu settings</summary>
            <div class="mt-4 max-w-xl">
                <label class="admin-label">
                    Internal slug
                    <input class="admin-input" name="slug" value="{{ old('slug', $menu->slug) }}" placeholder="Generated from the menu name">
                    <small class="mt-1 block text-xs font-normal leading-5 text-slate-500">Used internally. You normally do not need to change this after the menu is created.</small>
                </label>
            </div>
        </details>
    </x-admin.section-card>

    <div x-show="isHeader" x-cloak class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-extrabold text-slate-900">Live header structure</p>
                <p class="mt-1 text-xs leading-5 text-slate-600">Top-level items appear left-to-right in this order on both desktop and mobile. “Shop Products” automatically uses the live category tree for its mega menu.</p>
            </div>
            <a class="btn btn-white shrink-0" href="{{ route('admin.categories.index') }}">Manage Shop Categories</a>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Header menu preview">
            <template x-for="item in rootItems" :key="`preview-${item.key}`">
                <span
                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-slate-700"
                    :class="item.is_active ? '' : 'opacity-45 line-through'"
                >
                    <span x-text="item.label || 'Untitled item'"></span>
                    <span x-show="hasChildren(item) || isAutoShop(item)" class="text-slate-400">▾</span>
                </span>
            </template>
            <span x-show="rootItems.length === 0" class="text-xs text-slate-500">No top-level header items yet.</span>
        </div>
    </div>

    <x-admin.section-card
        title="Menu Items"
        description="Add, reorder, hide, or nest links. Reordering is limited to items at the same level so the hierarchy cannot be damaged accidentally."
    >
        <div class="space-y-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                    <label class="relative block flex-1">
                        <span class="sr-only">Search menu items</span>
                        <input
                            type="search"
                            class="admin-input"
                            x-model.trim="search"
                            placeholder="Search menu items..."
                        >
                    </label>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-white" x-on:click="setAllExpanded(true)">Expand all</button>
                        <button type="button" class="btn btn-white" x-on:click="setAllExpanded(false)">Collapse all</button>
                    </div>
                </div>
                <button type="button" class="btn btn-red shrink-0" x-on:click="addItem('')">+ Add top-level item</button>
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                <span><strong class="font-semibold text-slate-700" x-text="rootItems.length"></strong> top-level</span>
                <span><strong class="font-semibold text-slate-700" x-text="items.length - rootItems.length"></strong> submenu items</span>
                <span><strong class="font-semibold text-slate-700" x-text="items.filter(item => item.is_active).length"></strong> active</span>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in orderedItems" :key="item.key">
                    <article
                        x-show="matchesSearch(item)"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        :class="item.is_active ? '' : 'opacity-70'"
                    >
                        <input type="hidden" :name="`items[${index}][key]`" x-model="item.key">
                        <input type="hidden" :name="`items[${index}][sort_order]`" x-model="item.sort_order">

                        <div
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                            :style="`border-left: ${Math.min(depth(item), 4) * 4}px solid ${depth(item) ? '#cbd5e1' : 'transparent'}`"
                        >
                            <button type="button" class="min-w-0 flex-1 text-left" x-on:click="item.open = !item.open">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-extrabold text-slate-900" x-text="item.label || 'Untitled menu item'"></span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500" x-text="levelLabel(item)"></span>
                                    <span x-show="isHeader && isAutoShop(item)" class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700">Category mega menu</span>
                                    <span x-show="!item.is_active" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">Hidden</span>
                                </span>
                                <span class="mt-1 block truncate text-xs font-normal text-slate-500" x-text="destinationLabel(item)"></span>
                            </button>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="btn btn-white !px-3" x-on:click="moveSibling(item, -1)" :disabled="!canMove(item, -1)" title="Move up">↑</button>
                                <button type="button" class="btn btn-white !px-3" x-on:click="moveSibling(item, 1)" :disabled="!canMove(item, 1)" title="Move down">↓</button>
                                <button
                                    type="button"
                                    class="btn btn-white"
                                    x-show="!(isHeader && isAutoShop(item))"
                                    x-on:click="addItem(item.key)"
                                >+ Child</button>
                                <button type="button" class="btn btn-white" x-on:click="item.open = !item.open" x-text="item.open ? 'Done' : 'Edit'"></button>
                            </div>
                        </div>

                        <div x-show="item.open" x-cloak class="border-t border-slate-100 bg-slate-50/45 p-4">
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <label class="admin-label xl:col-span-2">
                                    Menu label
                                    <input class="admin-input" :name="`items[${index}][label]`" x-model="item.label" placeholder="e.g. Teamwear" required>
                                </label>

                                <label class="admin-label">
                                    Parent item
                                    <select
                                        class="admin-input"
                                        :name="`items[${index}][parent_key]`"
                                        x-model="item.parent_key"
                                        x-on:change="changeParent(item)"
                                    >
                                        <option value="">Top-level menu item</option>
                                        <template x-for="candidate in availableParents(item)" :key="candidate.key">
                                            <option :value="candidate.key" x-text="`${'— '.repeat(Math.min(depth(candidate), 3))}${candidate.label || 'Untitled item'}`"></option>
                                        </template>
                                    </select>
                                </label>

                                <label class="admin-label">
                                    Status
                                    <span class="flex min-h-[46px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-3">
                                        <input type="hidden" :name="`items[${index}][is_active]`" value="0">
                                        <input type="checkbox" :name="`items[${index}][is_active]`" value="1" x-model="item.is_active">
                                        <span class="text-sm font-semibold text-slate-700" x-text="item.is_active ? 'Visible' : 'Hidden'"></span>
                                    </span>
                                </label>

                                <label class="admin-label">
                                    Link to
                                    <select class="admin-input" :name="`items[${index}][link_type]`" x-model="item.link_type">
                                        <option value="category">Category</option>
                                        <option value="route">Store page</option>
                                        <option value="custom">Custom URL</option>
                                    </select>
                                </label>

                                <label class="admin-label md:col-span-1 xl:col-span-2" x-show="item.link_type === 'category'" x-cloak>
                                    Category
                                    <select class="admin-input" :name="`items[${index}][category_id]`" x-model="item.category_id">
                                        <option value="">Choose a category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->indented_name }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-label md:col-span-1 xl:col-span-2" x-show="item.link_type === 'route'" x-cloak>
                                    Store page
                                    <select class="admin-input" :name="`items[${index}][route_name]`" x-model="item.route_name">
                                        <option value="">Choose a page</option>
                                        @foreach($routeOptions as $routeName => $routeLabel)
                                            <option value="{{ $routeName }}">{{ $routeLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-label md:col-span-2 xl:col-span-3" x-show="item.link_type === 'custom'" x-cloak>
                                    Custom URL
                                    <input class="admin-input" :name="`items[${index}][url]`" x-model="item.url" placeholder="/products?deals=1 or https://example.com/page">
                                </label>

                            </div>

                            <div x-show="isHeader && isAutoShop(item)" x-cloak class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-800">
                                The Shop Products dropdown is automatically built from active menu-visible categories. Change its label, position, visibility, or destination here; manage the dropdown categories in Category Manager.
                            </div>

                            <details class="mt-4 rounded-xl border border-slate-200 bg-white p-3">
                                <summary class="cursor-pointer text-xs font-bold text-slate-600">Advanced item styling</summary>
                                <div class="mt-3 grid max-w-3xl gap-4 md:grid-cols-2">
                                    <label class="admin-label">
                                        Open link in
                                        <select class="admin-input" :name="`items[${index}][target]`" x-model="item.target">
                                            <option value="_self">Same tab</option>
                                            <option value="_blank">New tab</option>
                                        </select>
                                    </label>
                                    <label class="admin-label">
                                        CSS class
                                        <input class="admin-input" :name="`items[${index}][css_class]`" x-model="item.css_class" placeholder="Optional">
                                    </label>
                                </div>
                            </details>

                            <div class="mt-4 flex justify-end border-t border-slate-200 pt-4">
                                <button type="button" class="btn btn-white text-red-700" x-on:click="removeItem(item)">Remove item</button>
                            </div>
                        </div>
                    </article>
                </template>

                <div x-show="items.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <p class="font-bold text-slate-700">No menu items yet</p>
                    <p class="mt-1 text-sm text-slate-500">Add your first top-level item to start building this menu.</p>
                    <button type="button" class="btn btn-red mt-4" x-on:click="addItem('')">+ Add menu item</button>
                </div>

                <div x-show="items.length > 0 && orderedItems.every(item => !matchesSearch(item))" class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    No menu items match your search.
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">Saving updates the storefront navigation cache immediately.</p>
        <div class="flex flex-wrap justify-end gap-2">
            <a class="btn btn-white" href="{{ route('admin.menus.index') }}">Cancel</a>
            <button class="btn btn-red">{{ $isEdit ? 'Save Menu' : 'Create Menu' }}</button>
        </div>
    </div>
</div>

@once
<script>
function menuAdminForm(initial, initialLocation) {
    return {
        items: (initial || []).map((item) => ({
            ...item,
            parent_key: item.parent_key || '',
            target: item.target || '_self',
            css_class: item.css_class || '',
            sort_order: Number(item.sort_order || 0),
            is_active: item.is_active === true || item.is_active === 1 || item.is_active === '1',
            open: false,
            _previous_parent_key: item.parent_key || '',
        })),
        location: initialLocation || '',
        search: '',
        newItemSequence: 0,

        get isHeader() {
            return this.location === 'header-primary';
        },

        get rootItems() {
            return this.items
                .filter((item) => !item.parent_key)
                .sort((a, b) => Number(a.sort_order) - Number(b.sort_order));
        },

        get orderedItems() {
            const output = [];
            const visited = new Set();
            const walk = (parentKey = '') => {
                this.items
                    .filter((item) => (item.parent_key || '') === parentKey)
                    .sort((a, b) => Number(a.sort_order) - Number(b.sort_order))
                    .forEach((item) => {
                        if (visited.has(item.key)) return;
                        visited.add(item.key);
                        output.push(item);
                        walk(item.key);
                    });
            };
            walk('');
            this.items.forEach((item) => {
                if (!visited.has(item.key)) output.push(item);
            });
            return output;
        },

        isAutoShop(item) {
            const label = String(item?.label || '').toLowerCase().replace(/[-_]/g, ' ').replace(/\s+/g, ' ').trim();
            return item?.route_name === 'categories.index' || ['shop products', 'shop categories', 'categories'].includes(label);
        },

        hasChildren(item) {
            return this.items.some((candidate) => candidate.parent_key === item.key);
        },

        depth(item) {
            let depth = 0;
            let parentKey = item?.parent_key || '';
            const seen = new Set([item?.key]);
            while (parentKey && depth < 20 && !seen.has(parentKey)) {
                seen.add(parentKey);
                const parent = this.items.find((candidate) => candidate.key === parentKey);
                if (!parent) break;
                depth += 1;
                parentKey = parent.parent_key || '';
            }
            return depth;
        },

        levelLabel(item) {
            const level = this.depth(item);
            return level === 0 ? 'Top level' : `Level ${level + 1}`;
        },

        parentLabel(item) {
            if (!item.parent_key) return 'Top level';
            return this.items.find((candidate) => candidate.key === item.parent_key)?.label || 'Submenu';
        },

        destinationLabel(item) {
            if (item.link_type === 'category') {
                return item.category_id ? `Category link · ${this.parentLabel(item)}` : `Choose a category · ${this.parentLabel(item)}`;
            }
            if (item.link_type === 'route') {
                return item.route_name ? `Store page · ${item.route_name}` : `Choose a store page · ${this.parentLabel(item)}`;
            }
            return item.url ? `Custom link · ${item.url}` : `Enter a custom URL · ${this.parentLabel(item)}`;
        },

        matchesSearch(item) {
            if (!this.search) return true;
            const needle = this.search.toLowerCase();
            return [item.label, item.route_name, item.url, this.parentLabel(item)]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(needle));
        },

        isDescendant(candidateKey, ancestorKey) {
            let current = this.items.find((item) => item.key === candidateKey);
            const seen = new Set();
            while (current?.parent_key && !seen.has(current.parent_key)) {
                if (current.parent_key === ancestorKey) return true;
                seen.add(current.parent_key);
                current = this.items.find((item) => item.key === current.parent_key);
            }
            return false;
        },

        availableParents(item) {
            return this.orderedItems.filter((candidate) => {
                if (candidate.key === item.key) return false;
                if (this.isDescendant(candidate.key, item.key)) return false;
                if (this.isHeader && this.isAutoShop(candidate)) return false;
                return true;
            });
        },

        siblings(item) {
            const parentKey = item.parent_key || '';
            return this.items
                .filter((candidate) => (candidate.parent_key || '') === parentKey)
                .sort((a, b) => Number(a.sort_order) - Number(b.sort_order));
        },

        canMove(item, direction) {
            const siblings = this.siblings(item);
            const index = siblings.findIndex((candidate) => candidate.key === item.key);
            const target = index + direction;
            return index >= 0 && target >= 0 && target < siblings.length;
        },

        resequence(parentKey = '') {
            this.items
                .filter((item) => (item.parent_key || '') === (parentKey || ''))
                .sort((a, b) => Number(a.sort_order) - Number(b.sort_order))
                .forEach((item, index) => {
                    item.sort_order = index * 10;
                });
        },

        moveSibling(item, direction) {
            const siblings = this.siblings(item);
            const index = siblings.findIndex((candidate) => candidate.key === item.key);
            const targetIndex = index + direction;
            if (index < 0 || targetIndex < 0 || targetIndex >= siblings.length) return;

            const target = siblings[targetIndex];
            const currentOrder = Number(item.sort_order);
            item.sort_order = Number(target.sort_order);
            target.sort_order = currentOrder;
            this.resequence(item.parent_key || '');
        },

        addItem(parentKey = '') {
            if (this.isHeader && parentKey) {
                const parent = this.items.find((item) => item.key === parentKey);
                if (parent && this.isAutoShop(parent)) return;
            }

            this.newItemSequence += 1;
            const siblings = this.items.filter((item) => (item.parent_key || '') === (parentKey || ''));
            const item = {
                key: `item-new-${Date.now()}-${this.newItemSequence}`,
                parent_key: parentKey || '',
                label: '',
                link_type: 'route',
                category_id: '',
                route_name: '',
                url: '',
                target: '_self',
                css_class: '',
                is_active: true,
                sort_order: siblings.length * 10,
                open: true,
                _previous_parent_key: parentKey || '',
            };
            this.items.push(item);
            this.search = '';
        },

        changeParent(item) {
            const oldParent = item._previous_parent_key || '';
            const newParent = item.parent_key || '';
            if (oldParent === newParent) return;

            if (newParent && (newParent === item.key || this.isDescendant(newParent, item.key))) {
                item.parent_key = oldParent;
                return;
            }

            const newSiblings = this.items.filter((candidate) => candidate.key !== item.key && (candidate.parent_key || '') === newParent);
            item.sort_order = newSiblings.length * 10;
            item._previous_parent_key = newParent;
            this.resequence(oldParent);
            this.resequence(newParent);
        },

        removeItem(item) {
            const children = this.items.filter((candidate) => candidate.parent_key === item.key);
            if (children.length > 0) {
                const confirmed = window.confirm(`Remove “${item.label || 'this item'}”? Its ${children.length} direct child item${children.length === 1 ? '' : 's'} will move up one level.`);
                if (!confirmed) return;
                children.forEach((child) => {
                    child.parent_key = item.parent_key || '';
                    child._previous_parent_key = child.parent_key;
                });
            } else if (!window.confirm(`Remove “${item.label || 'this menu item'}”?`)) {
                return;
            }

            const parentKey = item.parent_key || '';
            this.items = this.items.filter((candidate) => candidate.key !== item.key);
            this.resequence(parentKey);
        },

        setAllExpanded(expanded) {
            this.items.forEach((item) => { item.open = expanded; });
        },
    };
}
</script>
@endonce
