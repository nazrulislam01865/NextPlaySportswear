<x-layouts.admin title="Category Ordering">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Catalog hierarchy</p>
            <h1 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Reorder Category Tree</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Drag categories inside the same parent group. Parent changes remain available from the category edit page, where circular relationships and maximum depth are validated.</p>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-white">Back to Categories</a>
    </div>

    <form method="POST" action="{{ route('admin.categories.ordering.update') }}" id="category-order-form" class="space-y-5">
        @csrf @method('PUT')
        @foreach($groups as $parentId => $categories)
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-[10px] font-black uppercase tracking-[.13em] text-slate-500">Sibling group</p>
                    <h2 class="mt-1 font-display text-2xl font-bold uppercase text-brand-ink">{{ $parentId ? ($parentLabels[$parentId] ?? 'Unknown parent') : 'Top-level categories' }}</h2>
                </div>
                <ol class="category-sort-list divide-y divide-slate-100" data-parent-id="{{ $parentId ?: '' }}">
                    @foreach($categories as $category)
                        <li class="category-sort-item flex cursor-grab items-center gap-4 px-5 py-4 active:cursor-grabbing" draggable="true" data-category-id="{{ $category->id }}">
                            <span class="select-none text-xl text-slate-400" aria-hidden="true">⋮⋮</span>
                            <div class="min-w-0 flex-1">
                                <strong class="block truncate text-brand-ink">{{ $category->name }}</strong>
                                <span class="font-mono text-[11px] text-slate-500">{{ $category->slug }}</span>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Current: {{ $category->sort_order }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endforeach

        <div id="category-order-inputs"></div>
        <div class="sticky bottom-3 z-20 flex flex-col gap-3 sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-white">Cancel</a>
            <button class="btn btn-red">Save Category Order</button>
        </div>
    </form>

    @once
        <script>
            (() => {
                const form = document.getElementById('category-order-form');
                const inputRoot = document.getElementById('category-order-inputs');
                let dragged = null;

                const rebuildInputs = () => {
                    inputRoot.innerHTML = '';
                    let index = 0;
                    document.querySelectorAll('.category-sort-list').forEach(list => {
                        list.querySelectorAll('.category-sort-item').forEach((item, position) => {
                            const values = {
                                id: item.dataset.categoryId,
                                parent_id: list.dataset.parentId,
                                sort_order: position,
                            };
                            Object.entries(values).forEach(([key, value]) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = `positions[${index}][${key}]`;
                                input.value = value;
                                inputRoot.appendChild(input);
                            });
                            index++;
                        });
                    });
                };

                document.querySelectorAll('.category-sort-item').forEach(item => {
                    item.addEventListener('dragstart', () => {
                        dragged = item;
                        item.classList.add('opacity-50');
                    });
                    item.addEventListener('dragend', () => {
                        item.classList.remove('opacity-50');
                        dragged = null;
                        rebuildInputs();
                    });
                    item.addEventListener('dragover', event => {
                        event.preventDefault();
                        if (!dragged || dragged.parentElement !== item.parentElement || dragged === item) return;
                        const bounds = item.getBoundingClientRect();
                        item.parentElement.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? item : item.nextSibling);
                    });
                });

                form.addEventListener('submit', rebuildInputs);
                rebuildInputs();
            })();
        </script>
    @endonce
</x-layouts.admin>
