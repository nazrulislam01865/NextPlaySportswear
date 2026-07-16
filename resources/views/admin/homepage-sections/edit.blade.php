@php
    $fields = $definition['fields'] ?? [];
    $hasText = in_array('text', $fields, true);
    $hasButtons = in_array('buttons', $fields, true);
    $hasImage = in_array('image', $fields, true);
    $hasItems = in_array('items', $fields, true);
    $itemFields = $definition['item_fields'] ?? ['title', 'description'];
    $currentImage = \App\Support\PublicMedia::url($section->image_path, $section->image_url, '');
    $items = old('items', $section->items ?: ($definition['items'] ?? []));
    $items = is_array($items) ? array_values($items) : [];
    $fieldLabels = [
        'icon' => $section->key === 'testimonials' ? 'Initials' : 'Icon',
        'title' => ($section->key === 'faq' ? 'Question' : ($section->key === 'testimonials' ? 'Customer name' : 'Title')),
        'subtitle' => $section->key === 'testimonials' ? 'Team / organization / location' : 'Small text',
        'description' => ($section->key === 'faq' ? 'Answer' : ($section->key === 'testimonials' ? 'Customer quote' : 'Description')),
        'url' => 'Link',
        'label' => $section->key === 'testimonials' ? 'Story tag' : 'Link label',
    ];
@endphp

<x-layouts.admin
    :title="$section->name"
    eyebrow="Homepage Section"
    :subtitle="'Update '.strtolower($section->name).' content.'"
    :storefront-url="route('home')"
>
    <form
        method="POST"
        action="{{ route('admin.homepage.sections.update', $section->key) }}"
        enctype="multipart/form-data"
        class="space-y-6"
        x-data="{
            imagePreview: @js($currentImage),
            removeImage: false,
            itemFields: @js(array_values($itemFields)),
            itemFieldLabels: @js($fieldLabels),
            items: @js($items),
            draftItem: {},
            editingIndex: null,
            init() {
                this.items = this.normalizeItems(this.items);
                this.resetItemDraft();
            },
            blankItem() {
                const item = {};
                this.itemFields.forEach((field) => item[field] = '');
                return item;
            },
            normalizeItem(item) {
                const normalized = this.blankItem();
                this.itemFields.forEach((field) => normalized[field] = String(item?.[field] ?? '').trim());
                return normalized;
            },
            normalizeItems(items) {
                return (Array.isArray(items) ? items : [])
                    .map((item) => this.normalizeItem(item))
                    .filter((item) => this.hasItemContent(item))
                    .slice(0, 30);
            },
            hasItemContent(item) {
                return this.itemFields.some((field) => String(item?.[field] ?? '').trim() !== '');
            },
            resetItemDraft() {
                this.draftItem = this.blankItem();
                this.editingIndex = null;
            },
            saveItem() {
                const item = this.normalizeItem(this.draftItem);
                if (!this.hasItemContent(item)) {
                    window.alert('Enter at least one item field before adding it to the list.');
                    return;
                }
                if (this.editingIndex === null && this.items.length >= 30) {
                    window.alert('A homepage section can contain up to 30 items.');
                    return;
                }
                if (this.editingIndex === null) {
                    this.items.push(item);
                } else {
                    this.items.splice(this.editingIndex, 1, item);
                }
                this.resetItemDraft();
            },
            editItem(index) {
                this.draftItem = this.normalizeItem(this.items[index] ?? {});
                this.editingIndex = index;
                this.$nextTick(() => this.$refs.itemEditor?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            },
            removeItem(index) {
                if (!window.confirm('Remove this item from the section?')) return;
                this.items.splice(index, 1);
                if (this.editingIndex === index) this.resetItemDraft();
                else if (this.editingIndex !== null && this.editingIndex > index) this.editingIndex--;
            },
            moveItem(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.items.length) return;
                const moved = this.items.splice(index, 1)[0];
                this.items.splice(target, 0, moved);
                if (this.editingIndex === index) this.editingIndex = target;
                else if (this.editingIndex === target) this.editingIndex = index;
            },
            itemPrimaryValue(item, index) {
                for (const field of ['title', 'label', 'subtitle', 'description', 'url', 'icon']) {
                    const value = String(item?.[field] ?? '').trim();
                    if (value) return value;
                }
                return `Item ${index + 1}`;
            },
            itemDetails(item) {
                const primary = this.itemPrimaryValue(item, -1);
                const details = this.itemFields
                    .filter((field) => String(item?.[field] ?? '').trim() && String(item[field]).trim() !== primary)
                    .map((field) => `${this.itemFieldLabels[field] ?? field}: ${String(item[field]).trim()}`)
                    .join(' • ');
                return details.length > 180 ? `${details.slice(0, 177)}...` : details;
            },
            validateItemDraft(event) {
                if (!this.hasItemContent(this.draftItem)) return;
                event.preventDefault();
                window.alert('Add or update the item in the list before saving the section.');
                this.$nextTick(() => this.$refs.itemEditor?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            },
            previewFile(event) {
                const file = event.target.files?.[0];
                if (!file) return;
                const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
                if (!allowed.includes(file.type) || file.size > 10 * 1024 * 1024) {
                    event.target.value = '';
                    window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 10 MB.');
                    return;
                }
                if (String(this.imagePreview).startsWith('blob:')) URL.revokeObjectURL(this.imagePreview);
                this.removeImage = false;
                this.imagePreview = URL.createObjectURL(file);
            }
        }"
        @submit="validateItemDraft($event)"
    >
        @csrf
        @method('PATCH')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.homepage.sections.index') }}" class="btn btn-white">← All Sections</a>
            @if($section->key === 'slider' && (auth('admin')->user()?->canAdmin('homepage_slides.view') ?? false))
                <a href="{{ route('admin.homepage-slides.index') }}" class="btn btn-red">Manage Slider Items</a>
            @endif
        </div>

        @if($section->key === 'slider')
            <x-admin.section-card title="Slider Items" description="This section controls the slider position and visibility. Use the button above to add or edit individual slider images and text.">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold text-brand-dark">
                    Keep this section active if you want the homepage slider to appear. Hide it if you only want the normal hero banner.
                </div>
            </x-admin.section-card>
        @endif

        @if($hasText)
            <x-admin.section-card title="Section Text" description="Only the basic text needed for this section.">
                <div class="grid gap-5">
                    <label class="admin-label">Eyebrow / small label
                        <input type="text" name="eyebrow" value="{{ old('eyebrow', $section->eyebrow) }}" class="admin-input @error('eyebrow') border-red-400 @enderror" maxlength="160" placeholder="Small label above the title">
                        @error('eyebrow')<span class="mt-2 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="admin-label">Title
                        <input type="text" name="title" value="{{ old('title', $section->title) }}" class="admin-input @error('title') border-red-400 @enderror" maxlength="255" placeholder="Section title">
                        @error('title')<span class="mt-2 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="admin-label">Description
                        <textarea name="description" class="admin-textarea @error('description') border-red-400 @enderror" maxlength="3000" rows="4" placeholder="Short section description">{{ old('description', $section->description) }}</textarea>
                        @error('description')<span class="mt-2 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
            </x-admin.section-card>
        @endif

        @if($hasButtons)
            <x-admin.section-card title="Buttons" description="Use internal paths like /products, anchors like #bulk, or secure external URLs.">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="mb-4 text-sm font-black text-brand-ink">Primary Button</h3>
                        <div class="space-y-4">
                            <label class="admin-label">Label<input type="text" name="primary_label" value="{{ old('primary_label', $section->primary_label) }}" class="admin-input @error('primary_label') border-red-400 @enderror" maxlength="160"></label>
                            <label class="admin-label">Destination<input type="text" name="primary_url" value="{{ old('primary_url', $section->primary_url) }}" class="admin-input @error('primary_url') border-red-400 @enderror" maxlength="2048" placeholder="/products or #products"></label>
                            @error('primary_url')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="mb-4 text-sm font-black text-brand-ink">Secondary Button</h3>
                        <div class="space-y-4">
                            <label class="admin-label">Label<input type="text" name="secondary_label" value="{{ old('secondary_label', $section->secondary_label) }}" class="admin-input @error('secondary_label') border-red-400 @enderror" maxlength="160"></label>
                            <label class="admin-label">Destination<input type="text" name="secondary_url" value="{{ old('secondary_url', $section->secondary_url) }}" class="admin-input @error('secondary_url') border-red-400 @enderror" maxlength="2048" placeholder="/bulk-quote or #bulk"></label>
                            @error('secondary_url')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </x-admin.section-card>
        @endif

        @if($hasImage)
            <x-admin.section-card title="Section Image" description="Upload one image for this section or use an existing secure image URL.">
                <div class="grid gap-5 lg:grid-cols-[280px_1fr]">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                        <template x-if="imagePreview && !removeImage"><img :src="imagePreview" alt="Current section image" class="h-56 w-full object-cover"></template>
                        <div x-show="!imagePreview || removeImage" class="grid h-56 place-items-center text-sm font-bold text-slate-500">No image selected</div>
                    </div>
                    <div class="space-y-4">
                        <label class="admin-label">Upload image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input @error('image_file') border-red-400 @enderror" @change="previewFile($event)"></label>
                        @error('image_file')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                        <label class="admin-label">Image URL<input type="text" name="image_url" value="{{ old('image_url', $section->image_url) }}" class="admin-input @error('image_url') border-red-400 @enderror" maxlength="2048" placeholder="https://... or /storage/..."></label>
                        @error('image_url')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                        <label class="admin-label">Alt text<input type="text" name="image_alt" value="{{ old('image_alt', $section->image_alt) }}" class="admin-input" maxlength="255" placeholder="Describe the image"></label>
                        <label class="inline-flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-700">
                            <input type="hidden" name="remove_image" value="0">
                            <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="h-4 w-4 rounded border-red-300 text-red-600">
                            Remove current image
                        </label>
                    </div>
                </div>
            </x-admin.section-card>
        @endif

        @if($hasItems)
            <x-admin.section-card :title="$definition['item_label'] ?? 'Items'" description="Use one form to add or edit an item. Saved items stay in the compact list below instead of opening every item at once.">
                <div class="space-y-5">
                    @if($errors->has('items') || count($errors->get('items.*')))
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
                            <p>Some item information needs attention.</p>
                            @foreach($errors->get('items.*') as $messages)
                                @foreach($messages as $message)
                                    <p class="mt-1 text-xs">{{ $message }}</p>
                                @endforeach
                            @endforeach
                        </div>
                    @endif

                    <div x-ref="itemEditor" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red" x-text="editingIndex === null ? 'Add item' : `Editing item ${editingIndex + 1}`"></p>
                                <h3 class="mt-1 text-lg font-black text-brand-ink" x-text="editingIndex === null ? 'Item information' : 'Update item information'"></h3>
                                <p class="mt-1 text-sm font-medium text-slate-600">Complete the fields below, then add the item to the list.</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-500 shadow-sm"><span x-text="items.length"></span>/30 items</span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($itemFields as $field)
                                @php
                                    $maxLength = match($field) {
                                        'icon' => 20,
                                        'label' => 160,
                                        'description' => 2000,
                                        'url' => 2048,
                                        default => 255,
                                    };
                                @endphp
                                @if($field === 'description')
                                    <label class="admin-label md:col-span-2">{{ $fieldLabels[$field] ?? ucfirst($field) }}
                                        <textarea class="admin-textarea" rows="3" maxlength="{{ $maxLength }}" x-model="draftItem.{{ $field }}" placeholder="Enter {{ strtolower($fieldLabels[$field] ?? $field) }}"></textarea>
                                    </label>
                                @else
                                    <label class="admin-label {{ count($itemFields) === 1 ? 'md:col-span-2' : '' }}">{{ $fieldLabels[$field] ?? ucfirst($field) }}
                                        <input type="text" class="admin-input" maxlength="{{ $maxLength }}" x-model="draftItem.{{ $field }}" placeholder="Enter {{ strtolower($fieldLabels[$field] ?? $field) }}">
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button type="button" class="btn btn-red" @click="saveItem()" x-text="editingIndex === null ? '+ Add Item to List' : 'Update Item in List'"></button>
                            <button type="button" class="btn btn-white" x-show="editingIndex !== null" x-cloak @click="resetItemDraft()">Cancel Editing</button>
                            <p class="text-xs font-bold text-slate-500 sm:ml-auto">The section is saved only after you click “Update Section”.</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                            <div>
                                <h3 class="text-sm font-black text-brand-ink">Added items</h3>
                                <p class="text-xs font-medium text-slate-500">Edit, remove, or change the display order.</p>
                            </div>
                            <span class="text-xs font-black text-slate-500" x-text="`${items.length} item${items.length === 1 ? '' : 's'}`"></span>
                        </div>

                        <div x-show="items.length === 0" x-cloak class="bg-white px-5 py-10 text-center">
                            <p class="text-sm font-black text-brand-ink">No items added yet</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">Use the form above to add the first item.</p>
                        </div>

                        <div x-show="items.length > 0" x-cloak class="hidden overflow-x-auto bg-white md:block">
                            <table class="min-w-full divide-y divide-slate-200 text-left">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-20 px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500">Order</th>
                                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500">Item</th>
                                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-500">Details</th>
                                        <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr :class="editingIndex === index ? 'bg-amber-50' : 'bg-white'">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-sm font-black text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" :disabled="index === 0" @click="moveItem(index, -1)" aria-label="Move item up">↑</button>
                                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-sm font-black text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" :disabled="index === items.length - 1" @click="moveItem(index, 1)" aria-label="Move item down">↓</button>
                                                </div>
                                            </td>
                                            <td class="max-w-xs px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-black text-slate-600" x-text="index + 1"></span>
                                                    <strong class="line-clamp-2 text-sm text-brand-ink" x-text="itemPrimaryValue(item, index)"></strong>
                                                </div>
                                            </td>
                                            <td class="max-w-xl px-4 py-3 text-xs font-medium leading-5 text-slate-600" x-text="itemDetails(item) || '—'"></td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50" @click="editItem(index)">Edit</button>
                                                    <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50" @click="removeItem(index)">Remove</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="items.length > 0" x-cloak class="divide-y divide-slate-100 bg-white md:hidden">
                            <template x-for="(item, index) in items" :key="index">
                                <article class="p-4" :class="editingIndex === index ? 'bg-amber-50' : 'bg-white'">
                                    <div class="flex items-start gap-3">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-black text-slate-600" x-text="index + 1"></span>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-black text-brand-ink" x-text="itemPrimaryValue(item, index)"></h4>
                                            <p class="mt-1 text-xs font-medium leading-5 text-slate-600" x-text="itemDetails(item) || 'No additional details'"></p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2 pl-11">
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 disabled:opacity-30" :disabled="index === 0" @click="moveItem(index, -1)">↑ Up</button>
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 disabled:opacity-30" :disabled="index === items.length - 1" @click="moveItem(index, 1)">↓ Down</button>
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700" @click="editItem(index)">Edit</button>
                                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-black text-red-600" @click="removeItem(index)">Remove</button>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </div>

                    <template x-for="(item, index) in items" :key="`hidden-${index}`">
                        <div>
                            <template x-for="field in itemFields" :key="`${index}-${field}`">
                                <input type="hidden" :name="`items[${index}][${field}]`" :value="item[field] ?? ''">
                            </template>
                        </div>
                    </template>
                </div>
            </x-admin.section-card>
        @endif

        <x-admin.section-card title="Publishing" description="Hide a section without deleting its content. Lower sort order appears first.">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="admin-label">Sort order
                    <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order ?? 0) }}" class="admin-input @error('sort_order') border-red-400 @enderror" min="0" max="1000000">
                    @error('sort_order')<span class="mt-2 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="flex items-center gap-3 self-end rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active)) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                    Show this section on homepage
                </label>
            </div>
        </x-admin.section-card>

        <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end">
            <a href="{{ route('admin.homepage.sections.index') }}" class="btn btn-white">Cancel</a>
            <button type="submit" class="btn btn-red">Update Section</button>
        </div>
    </form>
</x-layouts.admin>
