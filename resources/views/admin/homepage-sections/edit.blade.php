@php
    $fields = $definition['fields'] ?? [];
    $hasText = in_array('text', $fields, true);
    $hasButtons = in_array('buttons', $fields, true);
    $hasImage = in_array('image', $fields, true);
    $hasItems = in_array('items', $fields, true);
    $itemFields = $definition['item_fields'] ?? ['title', 'description'];
    $currentImage = \App\Support\PublicMedia::url($section->image_path, $section->image_url, '');
    $items = old('items', $section->items ?: ($definition['items'] ?? []));
    $items = is_array($items) && count($items) ? array_values($items) : [[]];
    $fieldLabels = [
        'icon' => 'Icon',
        'title' => ($section->key === 'faq' ? 'Question' : 'Title'),
        'subtitle' => 'Small text',
        'description' => ($section->key === 'faq' ? 'Answer' : 'Description'),
        'url' => 'Link',
        'label' => 'Link label',
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
            items: @js($items),
            addItem() { this.items.push({}); },
            removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
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
            <x-admin.section-card :title="$definition['item_label'] ?? 'Items'" description="Add, remove, and reorder the simple items displayed inside this section.">
                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-black text-brand-ink">Item <span x-text="index + 1"></span></h3>
                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50" @click="removeItem(index)">Remove</button>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach($itemFields as $field)
                                    @if($field === 'description')
                                        <label class="admin-label md:col-span-2">{{ $fieldLabels[$field] ?? ucfirst($field) }}
                                            <textarea class="admin-textarea" rows="3" x-model="item.{{ $field }}" :name="`items[${index}][{{ $field }}]`"></textarea>
                                        </label>
                                    @else
                                        <label class="admin-label">{{ $fieldLabels[$field] ?? ucfirst($field) }}
                                            <input type="text" class="admin-input" x-model="item.{{ $field }}" :name="`items[${index}][{{ $field }}]`">
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-white" @click="addItem()">+ Add Item</button>
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
