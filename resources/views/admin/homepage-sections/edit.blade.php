@php
    $fields = $definition['fields'] ?? [];
    $hasText = in_array('text', $fields, true);
    $hasButtons = in_array('buttons', $fields, true);
    $hasImage = in_array('image', $fields, true);
    $hasHeroSlides = in_array('hero_slides', $fields, true);
    $hasItems = in_array('items', $fields, true);
    $itemFields = $definition['item_fields'] ?? ['title', 'description'];
    $currentImage = \App\Support\PublicMedia::url($section->image_path, $section->image_url, '');
    $currentMobileImage = \App\Support\PublicMedia::url($section->mobile_image_path, $section->mobile_image_url, '');
    $items = old('items', $section->items ?: ($definition['items'] ?? []));
    $items = is_array($items) ? array_values($items) : [];
    $heroSlides = old('hero_slides', data_get($viewSection, 'hero_slides', []));
    $heroSlides = collect(is_array($heroSlides) ? $heroSlides : [])->map(function ($slide, $index): array {
        $slide = is_array($slide) ? $slide : [];
        $imagePath = trim((string) ($slide['image_path'] ?? ''));
        $imageUrl = trim((string) ($slide['image_url'] ?? ''));
        $preview = trim((string) ($slide['image'] ?? ''));
        if ($preview === '') {
            $preview = \App\Support\PublicMedia::url($imagePath ?: null, $imageUrl ?: null, '') ?: '';
        }

        return [
            'id' => trim((string) ($slide['id'] ?? '')) ?: 'hero-slide-'.($index + 1),
            'image_path' => $imagePath,
            'image_url' => $imageUrl,
            'image_alt' => trim((string) ($slide['image_alt'] ?? '')),
            'preview' => $preview,
        ];
    })->values()->all();
    $categoryOptionRows = collect($categoryOptions ?? [])->map(fn ($option) => [
        'id' => (string) ($option['id'] ?? ''),
        'label' => (string) ($option['label'] ?? ''),
        'display_label' => (string) ($option['display_label'] ?? $option['label'] ?? ''),
        'path' => (string) ($option['path'] ?? $option['label'] ?? ''),
        'level' => (string) ($option['level'] ?? 'category'),
        'level_label' => (string) ($option['level_label'] ?? $option['type'] ?? 'Category'),
        'depth' => (int) ($option['depth'] ?? 0),
    ])->filter(fn ($option) => $option['id'] !== '' && $option['display_label'] !== '')->values()->all();
    $categoryOptions = collect($categoryOptionRows)->mapWithKeys(fn ($option) => [$option['id'] => $option['display_label']])->all();
    $fieldLabels = [
        'icon' => $section->key === 'testimonials' ? 'Initials' : 'Icon',
        'title' => ($section->key === 'faq' ? 'Question' : ($section->key === 'testimonials' ? 'Customer name' : 'Title')),
        'subtitle' => $section->key === 'testimonials' ? 'Team / organization / location' : 'Small text',
        'description' => ($section->key === 'faq' ? 'Answer' : ($section->key === 'testimonials' ? 'Customer quote' : 'Description')),
        'url' => 'Link',
        'label' => $section->key === 'testimonials' ? 'Story tag' : 'Link label',
        'category_id' => 'Category / Subcategory / Sub-subcategory',
        'image_url' => 'Image URL override',
        'image_alt' => 'Image alt text',
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
            mobileImagePreview: @js($currentMobileImage),
            removeImage: false,
            removeMobileImage: false,
            itemFields: @js(array_values($itemFields)),
            itemFieldLabels: @js($fieldLabels),
            heroSlides: @js($heroSlides),
            heroSlideLimit: 12,
            categoryOptions: @js($categoryOptions),
            categoryOptionList: @js($categoryOptionRows),
            categorySearch: '',
            categoryLevelFilter: 'all',
            selectedCategoryIds: [],
            items: @js($items),
            draftItem: {},
            editingIndex: null,
            itemError: '',
            itemNotice: '',
            submittingAfterDraftSync: false,
            init() {
                this.items = this.normalizeItems(this.items);
                this.resetItemDraft();
            },
            hasCategoryField() {
                return this.itemFields.includes('category_id');
            },
            blankItem() {
                const item = {};
                this.itemFields.forEach((field) => item[field] = '');
                return item;
            },
            normalizeItem(item) {
                const normalized = this.blankItem();
                this.itemFields.forEach((field) => {
                    if (field === 'category_id') {
                        const value = parseInt(item?.[field] ?? 0, 10);
                        normalized[field] = Number.isFinite(value) && value > 0 ? String(value) : '';
                        return;
                    }
                    normalized[field] = String(item?.[field] ?? '').trim();
                });
                return normalized;
            },
            normalizeItems(items) {
                return (Array.isArray(items) ? items : [])
                    .map((item) => this.normalizeItem(item))
                    .filter((item) => this.hasItemContent(item));
            },
            hasItemContent(item) {
                return this.itemFields.some((field) => String(item?.[field] ?? '').trim() !== '');
            },
            clearItemFeedback() {
                this.itemError = '';
                this.itemNotice = '';
            },
            resetItemDraft(preserveFeedback = false) {
                this.draftItem = this.blankItem();
                this.editingIndex = null;
                this.selectedCategoryIds = [];
                this.categorySearch = '';
                this.categoryLevelFilter = 'all';
                if (!preserveFeedback) this.clearItemFeedback();
            },
            categoryOptionFor(categoryId) {
                const id = String(categoryId ?? '').trim();
                if (!id) return null;
                return this.categoryOptionList.find((option) => String(option.id) === id) ?? null;
            },
            categoryDisplayName(categoryId) {
                const option = this.categoryOptionFor(categoryId);
                return option?.path || option?.display_label || option?.label || `Category #${categoryId}`;
            },
            isCategorySelected(categoryId) {
                const id = String(categoryId ?? '').trim();
                return id !== '' && this.selectedCategoryIds.includes(id);
            },
            isCategoryAlreadyAdded(categoryId) {
                const id = String(categoryId ?? '').trim();
                if (!id) return false;
                return this.items.some((item, index) => index !== this.editingIndex && String(item?.category_id ?? '').trim() === id);
            },
            toggleCategorySelection(option) {
                const id = String(option?.id ?? '').trim();
                if (!id) return;
                this.clearItemFeedback();

                if (this.editingIndex !== null) {
                    if (this.isCategoryAlreadyAdded(id)) {
                        this.itemError = `“${this.categoryDisplayName(id)}” is already in this section. Choose a different category.`;
                        return;
                    }
                    this.selectedCategoryIds = [id];
                    this.draftItem.category_id = id;
                    return;
                }

                if (this.isCategorySelected(id)) {
                    this.selectedCategoryIds = this.selectedCategoryIds.filter((selectedId) => selectedId !== id);
                    this.draftItem.category_id = this.selectedCategoryIds[0] ?? '';
                    return;
                }

                if (this.isCategoryAlreadyAdded(id)) {
                    this.itemError = `“${this.categoryDisplayName(id)}” is already in this section. It cannot be added twice.`;
                    return;
                }

                this.selectedCategoryIds.push(id);
                this.draftItem.category_id = this.selectedCategoryIds[0] ?? '';
            },
            removeSelectedCategory(categoryId) {
                const id = String(categoryId ?? '').trim();
                this.selectedCategoryIds = this.selectedCategoryIds.filter((selectedId) => selectedId !== id);
                this.draftItem.category_id = this.selectedCategoryIds[0] ?? '';
                this.clearItemFeedback();
            },
            selectVisibleCategories() {
                if (this.editingIndex !== null) return;
                this.clearItemFeedback();

                const availableIds = this.filteredCategoryOptions()
                    .map((option) => String(option.id))
                    .filter((id) => !this.isCategoryAlreadyAdded(id));

                this.selectedCategoryIds = Array.from(new Set([...this.selectedCategoryIds, ...availableIds]));
                this.draftItem.category_id = this.selectedCategoryIds[0] ?? '';

                if (availableIds.length === 0) {
                    this.itemError = 'Every visible category is already in this section.';
                    return;
                }

                this.itemNotice = `${availableIds.length} visible categor${availableIds.length === 1 ? 'y was' : 'ies were'} selected.`;
            },
            clearCategorySelection() {
                this.selectedCategoryIds = [];
                this.draftItem.category_id = '';
                this.clearItemFeedback();
            },
            selectedCategoryOptions() {
                return this.selectedCategoryIds
                    .map((id) => this.categoryOptionFor(id))
                    .filter((option) => option !== null);
            },
            selectedCategoryOption() {
                return this.selectedCategoryOptions()[0] ?? null;
            },
            filteredCategoryOptions() {
                const search = String(this.categorySearch ?? '').trim().toLowerCase();
                return this.categoryOptionList
                    .filter((option) => this.categoryLevelFilter === 'all' || option.level === this.categoryLevelFilter)
                    .filter((option) => {
                        if (!search) return true;
                        return [option.label, option.display_label, option.path, option.level_label]
                            .some((value) => String(value ?? '').toLowerCase().includes(search));
                    })
                    .slice(0, 120);
            },
            duplicateCategoryGroups() {
                if (!this.hasCategoryField()) return [];
                const groups = {};
                this.items.forEach((item, index) => {
                    const id = String(item?.category_id ?? '').trim();
                    if (!id) return;
                    groups[id] ??= [];
                    groups[id].push(index + 1);
                });
                return Object.entries(groups)
                    .filter(([, positions]) => positions.length > 1)
                    .map(([id, positions]) => ({ id, positions, label: this.categoryDisplayName(id) }));
            },
            duplicateCategoryMessages() {
                return this.duplicateCategoryGroups().map((group) => {
                    const positions = group.positions.length === 2
                        ? `${group.positions[0]} and ${group.positions[1]}`
                        : `${group.positions.slice(0, -1).join(', ')}, and ${group.positions.at(-1)}`;
                    return `“${group.label}” appears more than once in items ${positions}.`;
                });
            },
            saveButtonLabel() {
                if (this.editingIndex !== null) return 'Update Item in List';
                if (!this.hasCategoryField()) return '+ Add Item to List';
                const count = this.selectedCategoryIds.length;
                if (count > 1) return `+ Add ${count} Selected Items`;
                return '+ Add Selected Item';
            },
            saveItem() {
                this.clearItemFeedback();
                const item = this.normalizeItem(this.draftItem);

                if (this.hasCategoryField()) {
                    const requestedIds = this.editingIndex !== null
                        ? [String(item.category_id ?? '').trim()]
                        : this.selectedCategoryIds;
                    const selectedIds = Array.from(new Set(requestedIds.filter((id) => String(id).trim() !== '')));

                    if (selectedIds.length === 0) {
                        this.itemError = this.editingIndex === null
                            ? 'Choose at least one category, subcategory, or sub-subcategory before adding it.'
                            : 'Choose a category, subcategory, or sub-subcategory before updating this item.';
                        return false;
                    }

                    const duplicateIds = selectedIds.filter((id) => this.isCategoryAlreadyAdded(id));
                    if (duplicateIds.length > 0) {
                        const names = duplicateIds.map((id) => `“${this.categoryDisplayName(id)}”`).join(', ');
                        this.itemError = `${names} ${duplicateIds.length === 1 ? 'is' : 'are'} already in this section. Remove the duplicate selection or choose another value.`;
                        return false;
                    }

                    if (this.editingIndex !== null) {
                        item.category_id = selectedIds[0];
                        this.items.splice(this.editingIndex, 1, item);
                        this.resetItemDraft(true);
                        this.itemNotice = `“${this.categoryDisplayName(selectedIds[0])}” was updated in the list.`;
                        return true;
                    }

                    selectedIds.forEach((categoryId) => {
                        this.items.push({ ...item, category_id: categoryId });
                    });

                    const addedNames = selectedIds.map((id) => this.categoryDisplayName(id));
                    this.resetItemDraft(true);
                    this.itemNotice = selectedIds.length === 1
                        ? `“${addedNames[0]}” was added to the list.`
                        : `${selectedIds.length} selected categories were added to the list.`;
                    return true;
                }

                if (!this.hasItemContent(item)) {
                    this.itemError = 'Complete at least one item field before adding it to the list.';
                    return false;
                }

                if (this.editingIndex === null) {
                    this.items.push(item);
                    this.resetItemDraft(true);
                    this.itemNotice = 'The item was added to the list.';
                } else {
                    this.items.splice(this.editingIndex, 1, item);
                    this.resetItemDraft(true);
                    this.itemNotice = 'The item was updated in the list.';
                }

                return true;
            },
            editItem(index) {
                this.clearItemFeedback();
                this.draftItem = this.normalizeItem(this.items[index] ?? {});
                this.editingIndex = index;
                const categoryId = String(this.draftItem?.category_id ?? '').trim();
                this.selectedCategoryIds = categoryId ? [categoryId] : [];
                this.categorySearch = '';
                this.categoryLevelFilter = 'all';
                this.$nextTick(() => this.$refs.itemEditor?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            },
            removeItem(index) {
                if (!window.confirm('Remove this item from the section?')) return;
                this.items.splice(index, 1);
                this.clearItemFeedback();
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
                const customTitle = String(item?.title ?? '').trim();
                if (customTitle) return customTitle;

                const categoryId = String(item?.category_id ?? '').trim();
                const categoryOption = this.categoryOptionFor(categoryId);
                if (categoryOption) {
                    return categoryOption.display_label || categoryOption.label || this.categoryOptions?.[categoryId];
                }
                for (const field of ['label', 'subtitle', 'description', 'url', 'image_url', 'icon']) {
                    const value = String(item?.[field] ?? '').trim();
                    if (value) return value;
                }
                return `Item ${index + 1}`;
            },
            itemDetails(item) {
                const primary = this.itemPrimaryValue(item, -1);
                const details = this.itemFields
                    .filter((field) => String(item?.[field] ?? '').trim() && String(item[field]).trim() !== primary)
                    .map((field) => {
                        if (field === 'category_id') {
                            const option = this.categoryOptionFor(item[field]);
                            const label = option?.path || this.categoryOptions?.[String(item[field]).trim()] || String(item[field]).trim();
                            const level = option?.level_label ? ` (${option.level_label})` : '';
                            return `${this.itemFieldLabels[field] ?? field}: ${label}${level}`;
                        }
                        return `${this.itemFieldLabels[field] ?? field}: ${String(item[field]).trim()}`;
                    })
                    .join(' • ');
                return details.length > 180 ? `${details.slice(0, 177)}...` : details;
            },
            prepareSubmit(event) {
                const hasPendingSelection = this.hasCategoryField() && this.selectedCategoryIds.length > 0;
                if (this.submittingAfterDraftSync || (!this.hasItemContent(this.draftItem) && !hasPendingSelection)) return;

                event.preventDefault();
                if (!this.saveItem()) return;

                this.submittingAfterDraftSync = true;
                this.$nextTick(() => event.target.submit());
            },
            addHeroSlide() {
                if (this.heroSlides.length >= this.heroSlideLimit) {
                    window.alert(`You can add up to ${this.heroSlideLimit} hero slider images.`);
                    return;
                }

                this.heroSlides.push({
                    id: `new-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    image_path: '',
                    image_url: '',
                    image_alt: '',
                    preview: '',
                });
                this.$nextTick(() => this.$refs.heroSlidesEnd?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            },
            removeHeroSlide(index) {
                const slide = this.heroSlides[index];
                if (!slide) return;
                if (!window.confirm('Remove this image from the hero slider?')) return;
                if (String(slide.preview || '').startsWith('blob:')) URL.revokeObjectURL(slide.preview);
                this.heroSlides.splice(index, 1);
            },
            moveHeroSlide(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.heroSlides.length) return;
                const moved = this.heroSlides.splice(index, 1)[0];
                this.heroSlides.splice(target, 0, moved);
            },
            previewHeroSlide(event, index) {
                const file = event.target.files?.[0];
                if (!file || !this.heroSlides[index]) return;
                const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
                if (!allowed.includes(file.type) || file.size > 10 * 1024 * 1024) {
                    event.target.value = '';
                    window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 10 MB.');
                    return;
                }
                const previous = String(this.heroSlides[index].preview || '');
                if (previous.startsWith('blob:')) URL.revokeObjectURL(previous);
                this.heroSlides[index].preview = URL.createObjectURL(file);
                this.heroSlides[index].image_url = '';
            },
            previewFile(event, target = 'desktop') {
                const file = event.target.files?.[0];
                if (!file) return;
                const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
                if (!allowed.includes(file.type) || file.size > 10 * 1024 * 1024) {
                    event.target.value = '';
                    window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 10 MB.');
                    return;
                }
                if (target === 'mobile') {
                    if (String(this.mobileImagePreview).startsWith('blob:')) URL.revokeObjectURL(this.mobileImagePreview);
                    this.removeMobileImage = false;
                    this.mobileImagePreview = URL.createObjectURL(file);
                    return;
                }
                if (String(this.imagePreview).startsWith('blob:')) URL.revokeObjectURL(this.imagePreview);
                this.removeImage = false;
                this.imagePreview = URL.createObjectURL(file);
            }
        }"
        @submit="prepareSubmit($event)"
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

        @if($hasHeroSlides)
            <x-admin.section-card title="Hero Image Slider" description="Upload and order the images shown inside the homepage hero card.">
                <div class="space-y-5">
                    <div class="admin-hero-slide-guidelines grid gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-brand-dark">
                        <div><span class="block text-xs font-black uppercase tracking-[.12em] text-blue-700">Recommended size</span><strong>1200 × 820 px</strong></div>
                        <div><span class="block text-xs font-black uppercase tracking-[.12em] text-blue-700">Aspect ratio</span><strong>60:41 (about 1.46:1)</strong></div>
                        <div><span class="block text-xs font-black uppercase tracking-[.12em] text-blue-700">Accepted files</span><strong>JPG, PNG, WebP, AVIF · 10 MB max</strong></div>
                        <p class="admin-hero-slide-guidelines-note text-xs font-semibold text-slate-600">Keep important jersey details near the center because the responsive storefront may crop a small amount from the image edges.</p>
                    </div>

                    @php
                        $heroSlideErrors = collect($errors->messages())
                            ->filter(fn ($messages, $field) => $field === 'hero_slides' || str_starts_with((string) $field, 'hero_slides.'))
                            ->flatten()->filter()->unique()->values();
                    @endphp
                    @if($heroSlideErrors->isNotEmpty())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
                            @foreach($heroSlideErrors as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-black text-brand-ink"><span x-text="heroSlides.length"></span> slider image<span x-show="heroSlides.length !== 1">s</span></p>
                            <p class="text-xs font-semibold text-slate-500">The first image loads first on the storefront.</p>
                        </div>
                        <button type="button" class="admin-hero-add-button btn btn-navy" @click="addHeroSlide" :disabled="heroSlides.length >= heroSlideLimit">+ Add Slider Image</button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(slide, index) in heroSlides" :key="slide.id">
                            <article class="admin-hero-slide-row grid gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="space-y-3">
                                    <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100" style="aspect-ratio: 60 / 41;">
                                        <template x-if="slide.preview"><img :src="slide.preview" :alt="slide.image_alt || `Hero slide ${index + 1}`" class="h-full w-full object-cover"></template>
                                        <div x-show="!slide.preview" class="grid h-full place-items-center p-5 text-center text-sm font-bold text-slate-500">Choose an image or enter a URL</div>
                                        <span class="absolute left-3 top-3 rounded-full bg-brand-navy px-3 py-1 text-xs font-black text-white" x-text="`Slide ${index + 1}`"></span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button type="button" class="btn btn-white px-3" @click="moveHeroSlide(index, -1)" :disabled="index === 0" aria-label="Move image earlier">↑</button>
                                        <button type="button" class="btn btn-white px-3" @click="moveHeroSlide(index, 1)" :disabled="index === heroSlides.length - 1" aria-label="Move image later">↓</button>
                                        <button type="button" class="btn border border-red-200 bg-red-50 px-3 text-red-700" @click="removeHeroSlide(index)" aria-label="Remove slider image">Remove</button>
                                    </div>
                                </div>

                                <div class="admin-hero-slide-fields grid gap-4">
                                    <input type="hidden" :name="`hero_slides[${index}][id]`" :value="slide.id">
                                    <input type="hidden" :name="`hero_slides[${index}][image_path]`" :value="slide.image_path || ''">
                                    <label class="admin-label">Upload image
                                        <input type="file" :name="`hero_slides[${index}][image_file]`" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input" @change="previewHeroSlide($event, index)">
                                    </label>
                                    <label class="admin-label">Image URL (optional alternative)
                                        <input type="text" :name="`hero_slides[${index}][image_url]`" x-model="slide.image_url" class="admin-input" maxlength="2048" placeholder="https://... or /images/..." @input="if (slide.image_url) slide.preview = slide.image_url">
                                    </label>
                                    <label class="admin-label">Image alt text
                                        <input type="text" :name="`hero_slides[${index}][image_alt]`" x-model="slide.image_alt" class="admin-input" maxlength="255" placeholder="Describe the jerseys or sportswear in this image">
                                    </label>
                                </div>
                            </article>
                        </template>
                        <div x-show="heroSlides.length === 0" class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm font-bold text-slate-500">No custom slider images selected. The storefront will use the built-in default images until you add one.</div>
                        <div x-ref="heroSlidesEnd"></div>
                    </div>
                </div>
            </x-admin.section-card>
        @endif

        @if($hasImage)
            <x-admin.section-card title="Responsive Section Images" description="For the homepage hero banner, use an 8:3 desktop/tablet image such as 2560×960 px and optionally add a separate mobile image such as 1080×1350 px.">
                <div class="grid gap-5 lg:grid-cols-[300px_1fr]">
                    <div class="space-y-4">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <template x-if="imagePreview && !removeImage"><img :src="imagePreview" alt="Current desktop section image" class="w-full object-cover" style="aspect-ratio: 8 / 3;"></template>
                            <div x-show="!imagePreview || removeImage" class="grid h-40 place-items-center text-sm font-bold text-slate-500">No desktop image selected</div>
                        </div>
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <template x-if="mobileImagePreview && !removeMobileImage"><img :src="mobileImagePreview" alt="Current mobile section image" class="w-full object-cover" style="aspect-ratio: 4 / 5;"></template>
                            <div x-show="!mobileImagePreview || removeMobileImage" class="grid h-48 place-items-center p-4 text-center text-sm font-bold text-slate-500">No mobile image selected</div>
                        </div>
                    </div>
                    <div class="space-y-5">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <h3 class="mb-3 text-sm font-black text-brand-ink">Desktop / tablet image</h3>
                            <label class="admin-label">Upload image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input @error('image_file') border-red-400 @enderror" @change="previewFile($event, 'desktop')"></label>
                            @error('image_file')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            <label class="admin-label mt-4">Image URL<input type="text" name="image_url" value="{{ old('image_url', $section->image_url) }}" class="admin-input @error('image_url') border-red-400 @enderror" maxlength="2048" placeholder="https://... or /storage/..."></label>
                            @error('image_url')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            <label class="admin-label mt-4">Alt text<input type="text" name="image_alt" value="{{ old('image_alt', $section->image_alt) }}" class="admin-input" maxlength="255" placeholder="Describe the image"></label>
                            <label class="mt-4 inline-flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-700">
                                <input type="hidden" name="remove_image" value="0">
                                <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="h-4 w-4 rounded border-red-300 text-red-600">
                                Remove current desktop image
                            </label>
                        </div>
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                            <h3 class="mb-3 text-sm font-black text-brand-ink">Mobile image</h3>
                            <label class="admin-label">Upload mobile image<input type="file" name="mobile_image_file" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input @error('mobile_image_file') border-red-400 @enderror" @change="previewFile($event, 'mobile')"></label>
                            @error('mobile_image_file')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            <label class="admin-label mt-4">Mobile image URL<input type="text" name="mobile_image_url" value="{{ old('mobile_image_url', $section->mobile_image_url) }}" class="admin-input @error('mobile_image_url') border-red-400 @enderror" maxlength="2048" placeholder="https://... or /storage/..."></label>
                            @error('mobile_image_url')<span class="block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                            <label class="admin-label mt-4">Mobile alt text<input type="text" name="mobile_image_alt" value="{{ old('mobile_image_alt', $section->mobile_image_alt) }}" class="admin-input" maxlength="255" placeholder="Optional. Defaults to desktop alt text."></label>
                            <label class="mt-4 inline-flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-700">
                                <input type="hidden" name="remove_mobile_image" value="0">
                                <input type="checkbox" name="remove_mobile_image" value="1" x-model="removeMobileImage" class="h-4 w-4 rounded border-red-300 text-red-600">
                                Remove current mobile image
                            </label>
                        </div>
                    </div>
                </div>
            </x-admin.section-card>
        @endif

        @if($hasItems)
            <x-admin.section-card :title="$definition['item_label'] ?? 'Items'" description="Use one form to add or edit an item. Saved items stay in the compact list below instead of opening every item at once.">
                <div class="space-y-5">
                    @php
                        $itemErrorMessages = collect($errors->messages())
                            ->filter(fn ($messages, $field) => $field === 'items' || str_starts_with((string) $field, 'items.'))
                            ->flatten()
                            ->filter()
                            ->unique()
                            ->values();
                    @endphp
                    @if($itemErrorMessages->isNotEmpty())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-black">Please review the selected homepage items.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs font-bold leading-5">
                                @foreach($itemErrorMessages as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(in_array('category_id', $itemFields, true))
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold leading-6 text-brand-dark">
                            Select one or several exact <strong>categories</strong>, <strong>subcategories</strong>, or <strong>sub-subcategories</strong> and add them together. Multi-selection and duplicate protection work for every category-driven homepage section, including What Are You Looking For?, Shop by Sport, and Best-Selling Team Gear. If the list is empty, the section falls back to its automatic catalog selection.
                        </div>

                        <div x-show="duplicateCategoryMessages().length > 0" x-cloak class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-black">Duplicate selections are already present in this list.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs font-bold leading-5">
                                <template x-for="message in duplicateCategoryMessages()" :key="message">
                                    <li x-text="message"></li>
                                </template>
                            </ul>
                            <p class="mt-2 text-xs font-semibold">Remove one copy of each named category before saving.</p>
                        </div>
                    @endif

                    <div x-ref="itemEditor" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red" x-text="editingIndex === null ? (hasCategoryField() ? 'Select items' : 'Add item') : `Editing item ${editingIndex + 1}`"></p>
                                <h3 class="mt-1 text-lg font-black text-brand-ink" x-text="editingIndex === null ? (hasCategoryField() ? 'Choose one or more homepage items' : 'Item information') : 'Update item information'"></h3>
                                <p class="mt-1 text-sm font-medium text-slate-600" x-text="hasCategoryField() && editingIndex === null ? 'Select several values and add them to the list together.' : 'Complete the fields below, then add or update the item.'"></p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-500 shadow-sm"><span x-text="items.length"></span> item<span x-text="items.length === 1 ? '' : 's'"></span></span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($itemFields as $field)
                                @php
                                    $maxLength = match($field) {
                                        'icon' => 20,
                                        'label' => 160,
                                        'description' => 2000,
                                        'url', 'image_url' => 2048,
                                        default => 255,
                                    };
                                @endphp
                                @if($field === 'category_id')
                                    <div class="{{ count($itemFields) === 1 ? 'md:col-span-2' : '' }}">
                                        <div class="flex flex-col gap-1">
                                            <span class="admin-label">{{ $fieldLabels[$field] ?? ucfirst($field) }}</span>
                                            <p class="text-xs font-semibold text-slate-500" x-text="editingIndex === null ? 'Choose one or more values. Each selected value will become its own homepage item.' : 'Choose one value for the item being edited.'"></p>
                                        </div>

                                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-4">
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                                                <input type="search" class="admin-input lg:flex-1" x-model.debounce.150ms="categorySearch" placeholder="Search category, subcategory, or sub-subcategory...">
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="button" class="rounded-full border px-3 py-2 text-xs font-black transition" :class="categoryLevelFilter === 'all' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'" @click="categoryLevelFilter = 'all'">All</button>
                                                    <button type="button" class="rounded-full border px-3 py-2 text-xs font-black transition" :class="categoryLevelFilter === 'category' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'" @click="categoryLevelFilter = 'category'">Categories</button>
                                                    <button type="button" class="rounded-full border px-3 py-2 text-xs font-black transition" :class="categoryLevelFilter === 'subcategory' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'" @click="categoryLevelFilter = 'subcategory'">Subcategories</button>
                                                    <button type="button" class="rounded-full border px-3 py-2 text-xs font-black transition" :class="categoryLevelFilter === 'sub_subcategory' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'" @click="categoryLevelFilter = 'sub_subcategory'">Sub-subcategories</button>
                                                </div>
                                            </div>

                                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                <span class="text-xs font-black text-slate-600">
                                                    <span x-text="selectedCategoryIds.length"></span>
                                                    selected
                                                    <span x-show="editingIndex === null">for bulk add</span>
                                                </span>
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="button" x-show="editingIndex === null" x-cloak class="rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-black text-blue-700 hover:bg-blue-50" @click="selectVisibleCategories()">Select visible</button>
                                                    <button type="button" x-show="selectedCategoryIds.length > 0" x-cloak class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-600 hover:bg-slate-100" @click="clearCategorySelection()">Clear selection</button>
                                                </div>
                                            </div>

                                            <div x-show="selectedCategoryOptions().length > 0" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                                                <p class="text-xs font-black uppercase tracking-wider text-emerald-800">Selected values</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="option in selectedCategoryOptions()" :key="`selected-${option.id}`">
                                                        <button type="button" class="inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-2 text-left text-xs font-bold text-emerald-800 shadow-sm hover:bg-emerald-100" @click="removeSelectedCategory(option.id)" :title="`Remove ${option.path || option.display_label} from the selection`">
                                                            <span class="truncate" x-text="option.path || option.display_label"></span>
                                                            <span class="shrink-0 text-base leading-none" aria-hidden="true">×</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="mt-3 max-h-80 overflow-y-auto rounded-xl border border-slate-100">
                                                <template x-for="option in filteredCategoryOptions()" :key="option.id">
                                                    <button
                                                        type="button"
                                                        class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm transition last:border-b-0"
                                                        :class="isCategorySelected(option.id) ? 'bg-blue-50 text-blue-800' : (isCategoryAlreadyAdded(option.id) ? 'bg-slate-50 text-slate-500 hover:bg-amber-50' : 'bg-white text-slate-700 hover:bg-blue-50')"
                                                        @click="toggleCategorySelection(option)"
                                                        :aria-pressed="isCategorySelected(option.id)"
                                                    >
                                                        <span
                                                            class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded border text-xs font-black"
                                                            :class="isCategorySelected(option.id) ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-transparent'"
                                                            aria-hidden="true"
                                                        >
                                                            <span x-show="isCategorySelected(option.id)" x-cloak>✓</span>
                                                        </span>
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block font-black text-brand-ink" x-text="option.display_label"></span>
                                                            <span class="mt-1 block text-xs font-medium text-slate-500" x-text="option.path"></span>
                                                        </span>
                                                        <span class="flex shrink-0 flex-col items-end gap-1">
                                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600" x-text="option.level_label"></span>
                                                            <span x-show="isCategoryAlreadyAdded(option.id) && !isCategorySelected(option.id)" x-cloak class="text-[10px] font-black uppercase tracking-wider text-amber-700">Already added</span>
                                                        </span>
                                                    </button>
                                                </template>
                                                <div x-show="filteredCategoryOptions().length === 0" x-cloak class="px-4 py-6 text-center text-sm font-bold text-slate-500">
                                                    No matching category found.
                                                </div>
                                            </div>

                                            <p class="mt-3 text-xs font-bold text-slate-500">Tip: use the filters and search, select several values, then add them together. Existing values are marked “Already added” and cannot be duplicated.</p>
                                        </div>
                                    </div>
                                @elseif($field === 'description')
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

                        <div x-show="itemError" x-cloak class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700" x-text="itemError"></div>
                        <div x-show="itemNotice" x-cloak class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" x-text="itemNotice"></div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button type="button" class="btn btn-red" @click="saveItem()" x-text="saveButtonLabel()"></button>
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
