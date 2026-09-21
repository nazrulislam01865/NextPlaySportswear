<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-navigation-editor>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-base font-black text-slate-900">Navigation Menu &amp; Mega Menu</h3>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">This is the menu used by the new Vue storefront. The existing approved menu is loaded here by default, so you edit it instead of rebuilding it. Desktop and mobile use the same list.</p>
        </div>
        <button type="button" data-nav-action="add-item" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Menu Item</button>
    </div>

    <div class="space-y-4" data-nav-list>
        @forelse($navigationItems as $itemIndex => $item)
            @php
                $mega = (array) ($item['mega_menu'] ?? []);
                $topChoices = (array) ($mega['top_choices'] ?? []);
                $topLinks = array_values(array_filter((array) ($topChoices['links'] ?? []), 'is_array'));
                $columns = array_values(array_filter((array) ($mega['columns'] ?? []), 'is_array'));
                $promo = (array) ($mega['promo'] ?? []);
                $promoImage = trim((string) ($promo['image'] ?? ''));
                $megaEnabled = (bool) ($mega['enabled'] ?? false);
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4" data-nav-item data-nav-key="{{ $itemIndex }}">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <input type="hidden" name="navigation[items][{{ $itemIndex }}][enabled]" value="0">
                            <input type="checkbox" name="navigation[items][{{ $itemIndex }}][enabled]" value="1" @checked((bool) ($item['enabled'] ?? true))>
                            Show menu item
                        </label>
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][enabled]" value="0">
                            <input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][enabled]" value="1" @checked($megaEnabled) data-mega-toggle>
                            Enable mega menu
                        </label>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" data-nav-action="item-up" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                        <button type="button" data-nav-action="item-down" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                        <button type="button" data-nav-action="remove-item" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[1fr_1.4fr_180px]">
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Menu label</label>
                        <input name="navigation[items][{{ $itemIndex }}][label]" value="{{ $item['label'] ?? '' }}" maxlength="120" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="SHOP">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label>
                        <input name="navigation[items][{{ $itemIndex }}][url]" value="{{ $item['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="/products">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Open link</label>
                        <select name="navigation[items][{{ $itemIndex }}][target]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                            <option value="_self" @selected(($item['target'] ?? '_self') === '_self')>Same tab</option>
                            <option value="_blank" @selected(($item['target'] ?? '_self') === '_blank')>New tab</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 space-y-5 rounded-2xl border border-slate-200 bg-white p-4 {{ $megaEnabled ? '' : 'hidden' }}" data-mega-panel>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-900">Top Choices Rail</h4>
                                <p class="mt-1 text-xs text-slate-500">Controls the left rail in the current mega-menu design.</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                    <input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][enabled]" value="0">
                                    <input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][enabled]" value="1" @checked((bool) ($topChoices['enabled'] ?? true))>
                                    Show rail
                                </label>
                                <button type="button" data-nav-action="add-top-link" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">+ Add Link</button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Eyebrow</label>
                            <input name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][eyebrow]" value="{{ $topChoices['eyebrow'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Top Choices">
                        </div>
                        <div class="space-y-3" data-top-links>
                            @foreach($topLinks as $topIndex => $link)
                                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 lg:grid-cols-[auto_1fr_1.5fr_auto] lg:items-end" data-top-link>
                                    <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][links][{{ $topIndex }}][enabled]" value="0"><input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][links][{{ $topIndex }}][enabled]" value="1" @checked((bool) ($link['enabled'] ?? true))> Show</label>
                                    <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">Label</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][links][{{ $topIndex }}][label]" value="{{ $link['label'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                                    <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">URL</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][top_choices][links][{{ $topIndex }}][url]" value="{{ $link['url'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                                    <div class="flex gap-1 pb-1"><button type="button" data-nav-action="top-up" class="rounded-md border px-2 py-1 text-xs">↑</button><button type="button" data-nav-action="top-down" class="rounded-md border px-2 py-1 text-xs">↓</button><button type="button" data-nav-action="remove-top" class="rounded-md border border-red-200 px-2 py-1 text-xs text-red-600">×</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-900">Mega Menu Columns</h4>
                                <p class="mt-1 text-xs text-slate-500">The current design uses four columns, but you can add, remove and reorder them.</p>
                            </div>
                            <button type="button" data-nav-action="add-column" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">+ Add Column</button>
                        </div>
                        <div class="space-y-4" data-columns>
                            @foreach($columns as $columnIndex => $column)
                                @php $columnLinks = array_values(array_filter((array) ($column['links'] ?? []), 'is_array')); @endphp
                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3" data-column data-column-key="{{ $columnIndex }}">
                                    <div class="mb-3 flex flex-wrap items-end gap-3">
                                        <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][enabled]" value="0"><input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][enabled]" value="1" @checked((bool) ($column['enabled'] ?? true))> Show</label>
                                        <div class="min-w-[220px] flex-1"><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">Column title</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][title]" value="{{ $column['title'] ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"></div>
                                        <button type="button" data-nav-action="add-column-link" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700">+ Add Link</button>
                                        <div class="flex gap-1"><button type="button" data-nav-action="column-up" class="rounded-md border bg-white px-2 py-1 text-xs">↑</button><button type="button" data-nav-action="column-down" class="rounded-md border bg-white px-2 py-1 text-xs">↓</button><button type="button" data-nav-action="remove-column" class="rounded-md border border-red-200 bg-white px-2 py-1 text-xs text-red-600">Remove</button></div>
                                    </div>
                                    <div class="space-y-2" data-column-links>
                                        @foreach($columnLinks as $linkIndex => $link)
                                            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 lg:grid-cols-[auto_1fr_1.5fr_auto] lg:items-end" data-column-link>
                                                <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][links][{{ $linkIndex }}][enabled]" value="0"><input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][links][{{ $linkIndex }}][enabled]" value="1" @checked((bool) ($link['enabled'] ?? true))> Show</label>
                                                <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">Label</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][links][{{ $linkIndex }}][label]" value="{{ $link['label'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                                                <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">URL</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][columns][{{ $columnIndex }}][links][{{ $linkIndex }}][url]" value="{{ $link['url'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                                                <div class="flex gap-1 pb-1"><button type="button" data-nav-action="column-link-up" class="rounded-md border px-2 py-1 text-xs">↑</button><button type="button" data-nav-action="column-link-down" class="rounded-md border px-2 py-1 text-xs">↓</button><button type="button" data-nav-action="remove-column-link" class="rounded-md border border-red-200 px-2 py-1 text-xs text-red-600">×</button></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4" data-promo-field>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div><h4 class="text-sm font-black text-slate-900">Promo Card</h4><p class="mt-1 text-xs text-slate-500">Controls the image and CTA on the right side of the mega menu.</p></div>
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][enabled]" value="0"><input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][enabled]" value="1" @checked((bool) ($promo['enabled'] ?? true))> Show promo</label>
                        </div>
                        <div class="grid gap-4 xl:grid-cols-[260px_1fr]">
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Promo image</label>
                                <input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][image]" value="{{ $promoImage }}">
                                <div class="mb-3 flex h-36 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <img src="{{ $promoImage }}" alt="" class="h-full w-full object-cover {{ $promoImage !== '' ? '' : 'hidden' }}" data-promo-preview>
                                    <span class="text-xs font-bold text-slate-400 {{ $promoImage !== '' ? 'hidden' : '' }}" data-promo-placeholder>NO IMAGE</span>
                                </div>
                                <input type="file" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][image_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700" data-promo-file>
                                <input type="hidden" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][remove_image]" value="0">
                                @if($promoImage !== '')
                                    <label class="mt-2 flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="navigation[items][{{ $itemIndex }}][mega_menu][promo][remove_image]" value="1" data-remove-promo> Clear current image</label>
                                @endif
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2"><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Image alt text</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][promo][alt]" value="{{ $promo['alt'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                                <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">CTA label</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][promo][label]" value="{{ $promo['label'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                                <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">CTA URL</label><input name="navigation[items][{{ $itemIndex }}][mega_menu][promo][url]" value="{{ $promo['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-nav-empty>No header menu items. Add one to show navigation in the Vue storefront.</div>
        @endforelse
    </div>

    @error('navigation')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    @error('navigation.*')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</section>

<script>
(() => {
    const section = document.querySelector('[data-navigation-editor]');
    if (!section) return;
    let sequence = Date.now();
    const key = (prefix) => `${prefix}_${sequence++}`;

    const controls = (kind) => `<div class="flex gap-1 pb-1"><button type="button" data-nav-action="${kind}-up" class="rounded-md border px-2 py-1 text-xs">↑</button><button type="button" data-nav-action="${kind}-down" class="rounded-md border px-2 py-1 text-xs">↓</button><button type="button" data-nav-action="remove-${kind}" class="rounded-md border border-red-200 px-2 py-1 text-xs text-red-600">×</button></div>`;

    const linkRow = (itemKey, scope, linkKey, columnKey = null) => {
        const prefix = columnKey === null
            ? `navigation[items][${itemKey}][mega_menu][top_choices][links][${linkKey}]`
            : `navigation[items][${itemKey}][mega_menu][columns][${columnKey}][links][${linkKey}]`;
        const kind = columnKey === null ? 'top' : 'column-link';
        const marker = columnKey === null ? 'data-top-link' : 'data-column-link';
        return `<div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 lg:grid-cols-[auto_1fr_1.5fr_auto] lg:items-end" ${marker}>
            <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="${prefix}[enabled]" value="0"><input type="checkbox" name="${prefix}[enabled]" value="1" checked> Show</label>
            <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">Label</label><input name="${prefix}[label]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">URL</label><input name="${prefix}[url]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="/products"></div>
            ${controls(kind)}
        </div>`;
    };

    const columnRow = (itemKey, columnKey) => `<div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3" data-column data-column-key="${columnKey}">
        <div class="mb-3 flex flex-wrap items-end gap-3">
            <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="navigation[items][${itemKey}][mega_menu][columns][${columnKey}][enabled]" value="0"><input type="checkbox" name="navigation[items][${itemKey}][mega_menu][columns][${columnKey}][enabled]" value="1" checked> Show</label>
            <div class="min-w-[220px] flex-1"><label class="mb-2 block text-[11px] font-black uppercase tracking-wide text-slate-500">Column title</label><input name="navigation[items][${itemKey}][mega_menu][columns][${columnKey}][title]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="COLUMN"></div>
            <button type="button" data-nav-action="add-column-link" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700">+ Add Link</button>
            <div class="flex gap-1"><button type="button" data-nav-action="column-up" class="rounded-md border bg-white px-2 py-1 text-xs">↑</button><button type="button" data-nav-action="column-down" class="rounded-md border bg-white px-2 py-1 text-xs">↓</button><button type="button" data-nav-action="remove-column" class="rounded-md border border-red-200 bg-white px-2 py-1 text-xs text-red-600">Remove</button></div>
        </div>
        <div class="space-y-2" data-column-links></div>
    </div>`;

    const itemRow = (itemKey) => `<article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4" data-nav-item data-nav-key="${itemKey}">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-sm font-bold text-slate-800"><input type="hidden" name="navigation[items][${itemKey}][enabled]" value="0"><input type="checkbox" name="navigation[items][${itemKey}][enabled]" value="1" checked> Show menu item</label>
                <label class="flex items-center gap-2 text-sm font-bold text-slate-800"><input type="hidden" name="navigation[items][${itemKey}][mega_menu][enabled]" value="0"><input type="checkbox" name="navigation[items][${itemKey}][mega_menu][enabled]" value="1" data-mega-toggle> Enable mega menu</label>
            </div>
            <div class="flex items-center gap-2"><button type="button" data-nav-action="item-up" class="rounded-lg border bg-white px-2.5 py-1.5 text-xs font-bold">Up</button><button type="button" data-nav-action="item-down" class="rounded-lg border bg-white px-2.5 py-1.5 text-xs font-bold">Down</button><button type="button" data-nav-action="remove-item" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-bold text-red-600">Remove</button></div>
        </div>
        <div class="grid gap-4 lg:grid-cols-[1fr_1.4fr_180px]">
            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Menu label</label><input name="navigation[items][${itemKey}][label]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="MENU"></div>
            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="navigation[items][${itemKey}][url]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="/page"></div>
            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Open link</label><select name="navigation[items][${itemKey}][target]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="_self">Same tab</option><option value="_blank">New tab</option></select></div>
        </div>
        <div class="mt-5 hidden space-y-5 rounded-2xl border border-slate-200 bg-white p-4" data-mega-panel>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-4 flex items-center justify-between gap-3"><div><h4 class="text-sm font-black">Top Choices Rail</h4></div><div class="flex items-center gap-3"><label class="flex items-center gap-2 text-xs font-bold"><input type="hidden" name="navigation[items][${itemKey}][mega_menu][top_choices][enabled]" value="0"><input type="checkbox" name="navigation[items][${itemKey}][mega_menu][top_choices][enabled]" value="1" checked> Show rail</label><button type="button" data-nav-action="add-top-link" class="rounded-lg border px-3 py-2 text-xs font-bold">+ Add Link</button></div></div>
                <div class="mb-4"><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Eyebrow</label><input name="navigation[items][${itemKey}][mega_menu][top_choices][eyebrow]" value="Top Choices" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div class="space-y-3" data-top-links></div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4"><div class="mb-4 flex items-center justify-between gap-3"><h4 class="text-sm font-black">Mega Menu Columns</h4><button type="button" data-nav-action="add-column" class="rounded-lg border px-3 py-2 text-xs font-bold">+ Add Column</button></div><div class="space-y-4" data-columns></div></div>
            <div class="rounded-xl border border-slate-200 p-4" data-promo-field>
                <div class="mb-4 flex items-center justify-between gap-3"><h4 class="text-sm font-black">Promo Card</h4><label class="flex items-center gap-2 text-xs font-bold"><input type="hidden" name="navigation[items][${itemKey}][mega_menu][promo][enabled]" value="0"><input type="checkbox" name="navigation[items][${itemKey}][mega_menu][promo][enabled]" value="1" checked> Show promo</label></div>
                <div class="grid gap-4 xl:grid-cols-[260px_1fr]"><div><input type="hidden" name="navigation[items][${itemKey}][mega_menu][promo][image]" value=""><div class="mb-3 flex h-36 items-center justify-center overflow-hidden rounded-xl border bg-slate-50"><img alt="" class="hidden h-full w-full object-cover" data-promo-preview><span class="text-xs font-bold text-slate-400" data-promo-placeholder>NO IMAGE</span></div><input type="file" name="navigation[items][${itemKey}][mega_menu][promo][image_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs" data-promo-file><input type="hidden" name="navigation[items][${itemKey}][mega_menu][promo][remove_image]" value="0"></div><div class="grid gap-4 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-2 block text-xs font-black uppercase text-slate-500">Image alt text</label><input name="navigation[items][${itemKey}][mega_menu][promo][alt]" class="w-full rounded-xl border px-3 py-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-black uppercase text-slate-500">CTA label</label><input name="navigation[items][${itemKey}][mega_menu][promo][label]" class="w-full rounded-xl border px-3 py-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-black uppercase text-slate-500">CTA URL</label><input name="navigation[items][${itemKey}][mega_menu][promo][url]" class="w-full rounded-xl border px-3 py-2.5 text-sm"></div></div></div>
            </div>
        </div>
    </article>`;

    const move = (row, direction, selector) => {
        const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling?.matches(selector)) return;
        if (direction === 'up') row.parentElement.insertBefore(row, sibling);
        else row.parentElement.insertBefore(sibling, row);
    };

    section.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (input.matches('[data-mega-toggle]')) {
            input.closest('[data-nav-item]')?.querySelector('[data-mega-panel]')?.classList.toggle('hidden', !input.checked);
        }
        if (input.matches('[data-promo-file]')) {
            const field = input.closest('[data-promo-field]');
            const file = input.files?.[0];
            const preview = field?.querySelector('[data-promo-preview]');
            if (!file || !(preview instanceof HTMLImageElement)) return;
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            field?.querySelector('[data-promo-placeholder]')?.classList.add('hidden');
            const remove = field?.querySelector('[data-remove-promo]');
            if (remove instanceof HTMLInputElement) remove.checked = false;
        }
        if (input.matches('[data-remove-promo]') && input.checked) {
            const field = input.closest('[data-promo-field]');
            const preview = field?.querySelector('[data-promo-preview]');
            const fileInput = field?.querySelector('[data-promo-file]');
            if (preview instanceof HTMLImageElement) { preview.src = ''; preview.classList.add('hidden'); }
            if (fileInput instanceof HTMLInputElement) fileInput.value = '';
            field?.querySelector('[data-promo-placeholder]')?.classList.remove('hidden');
        }
    });

    section.addEventListener('click', (event) => {
        const button = event.target.closest('[data-nav-action]');
        if (!button) return;
        const action = button.dataset.navAction;
        const item = button.closest('[data-nav-item]');
        const itemKey = item?.dataset.navKey;

        if (action === 'add-item') {
            section.querySelector('[data-nav-empty]')?.remove();
            section.querySelector('[data-nav-list]')?.insertAdjacentHTML('beforeend', itemRow(key('item')));
            return;
        }
        if (!item || itemKey === undefined) return;

        if (action === 'item-up') return move(item, 'up', '[data-nav-item]');
        if (action === 'item-down') return move(item, 'down', '[data-nav-item]');
        if (action === 'remove-item') { item.remove(); return; }
        if (action === 'add-top-link') return item.querySelector('[data-top-links]')?.insertAdjacentHTML('beforeend', linkRow(itemKey, 'top', key('top')));
        if (action === 'add-column') {
            const columns = item.querySelector('[data-columns]');
            if (!columns || columns.querySelectorAll('[data-column]').length >= 4) return;
            columns.insertAdjacentHTML('beforeend', columnRow(itemKey, key('column')));
            return;
        }

        const top = button.closest('[data-top-link]');
        if (top) {
            if (action === 'top-up') return move(top, 'up', '[data-top-link]');
            if (action === 'top-down') return move(top, 'down', '[data-top-link]');
            if (action === 'remove-top') { top.remove(); return; }
        }

        const column = button.closest('[data-column]');
        if (column) {
            if (action === 'column-up') return move(column, 'up', '[data-column]');
            if (action === 'column-down') return move(column, 'down', '[data-column]');
            if (action === 'remove-column') { column.remove(); return; }
            if (action === 'add-column-link') return column.querySelector('[data-column-links]')?.insertAdjacentHTML('beforeend', linkRow(itemKey, 'column', key('link'), column.dataset.columnKey));
        }

        const columnLink = button.closest('[data-column-link]');
        if (columnLink) {
            if (action === 'column-link-up') return move(columnLink, 'up', '[data-column-link]');
            if (action === 'column-link-down') return move(columnLink, 'down', '[data-column-link]');
            if (action === 'remove-column-link') { columnLink.remove(); }
        }
    });
})();
</script>
