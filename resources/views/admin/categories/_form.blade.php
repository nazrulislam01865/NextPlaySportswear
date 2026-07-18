@php
    $isEdit = $category->exists;
    $currentFilters = $category->relationLoaded('filters') ? $category->filters->keyBy('id') : collect();
    $initialParentId = (string) old('parent_id', $category->parent_id ?? '');
    $initialIsFeatured = filter_var(old('is_featured', $category->is_featured ?? false), FILTER_VALIDATE_BOOLEAN);
    $initialPreview = $category->thumbnailUrl();
    $initialIconPreview = $category->iconUrl();
@endphp

<form
    method="POST"
    enctype="multipart/form-data"
    action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
    class="space-y-6"
    x-data="categoryAdminForm(@js([
        'name' => old('name', $category->name),
        'slug' => old('slug', $category->slug),
        'preview' => $initialPreview,
        'iconPreview' => $initialIconPreview,
        'parentId' => $initialParentId,
        'isFeatured' => $initialIsFeatured,
    ]))"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800" role="alert">
            <strong class="block font-black">Please correct the highlighted information:</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <nav class="sticky top-20 z-20 -mx-4 overflow-x-auto border-y border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="flex min-w-max gap-2 text-sm font-bold">
            @foreach([
                ['basic', 'Basic Info'],
                ['media', 'Image'],
                ['display', 'Homepage / Storefront'],
                ['products', 'Products'],
                ['advanced', 'Advanced'],
            ] as [$anchor, $label])
                <a href="#{{ $anchor }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-brand-dark hover:text-white">{{ $label }}</a>
            @endforeach
            @if($isEdit)
                <a href="{{ route('admin.categories.products.index', $category) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-brand-blue hover:bg-brand-blue hover:text-white">Manage Products</a>
            @endif
        </div>
    </nav>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <x-admin.section-card
                id="basic"
                title="Basic Category Info"
                description="Keep this simple. The slug is generated automatically from the category name when you save."
            >
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="admin-label md:col-span-2">
                        Category name
                        <input
                            class="admin-input"
                            name="name"
                            x-model="name"
                            x-on:input="updateSlug()"
                            value="{{ old('name', $category->name) }}"
                            required
                            maxlength="160"
                            autocomplete="off"
                            placeholder="Example: Baseball Jerseys"
                        >
                        <small class="font-normal text-slate-500">This is the title customers will see.</small>
                    </label>

                    <div class="md:col-span-2 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-blue">Auto URL</p>
                        <p class="mt-2 break-all font-mono text-sm font-bold text-slate-800">
                            /category/<span x-text="slug || 'category-name'"></span>
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            You do not need to type the slug. It is generated from the name. If the same slug already exists, the system adds a number automatically.
                        </p>
                    </div>

                    <label class="admin-label">
                        Parent category
                        <select class="admin-input" name="parent_id" x-model="parentId">
                            <option value="">None — parent category</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                                    {{ $parent->indented_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="font-normal text-slate-500">Only parent categories can be featured in the homepage category section.</small>
                    </label>

                    <label class="admin-label">
                        Category type
                        <select class="admin-input" name="category_type">
                            @foreach([
                                'standard' => 'Standard',
                                'sport' => 'Sport',
                                'collection' => 'Collection',
                                'apparel' => 'Apparel',
                                'accessory' => 'Accessory',
                                'promotional' => 'Promotional',
                                'sale' => 'Sale',
                                'new-arrival' => 'New Arrival',
                                'navigation-only' => 'Navigation Only',
                            ] as $type => $label)
                                <option value="{{ $type }}" @selected(old('category_type', $category->category_type ?? 'standard') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="font-normal text-slate-500">Use Sport for Baseball, Basketball, Soccer, and similar categories.</small>
                    </label>

                    <label class="admin-label">
                        Menu label <span class="font-normal text-slate-400">(optional)</span>
                        <input
                            class="admin-input"
                            name="menu_label"
                            value="{{ old('menu_label', $category->menu_label) }}"
                            maxlength="160"
                            placeholder="Shorter navigation label"
                        >
                    </label>

                    <label class="admin-label">
                        CTA label
                        <input
                            class="admin-input"
                            name="cta_label"
                            value="{{ old('cta_label', $category->cta_label ?? 'View Category') }}"
                            required
                            maxlength="160"
                            placeholder="View Category"
                        >
                        <small class="font-normal text-slate-500">Button text used on storefront category cards.</small>
                    </label>

                    <label class="admin-label md:col-span-2">
                        Short description
                        <textarea
                            class="admin-textarea min-h-28"
                            name="short_description"
                            maxlength="1500"
                            placeholder="Short card description for homepage/category cards..."
                        >{{ old('short_description', $category->short_description) }}</textarea>
                    </label>

                    <label class="admin-label md:col-span-2">
                        Full description
                        <textarea
                            class="admin-textarea min-h-40"
                            name="description"
                            maxlength="10000"
                            placeholder="Full category page description..."
                        >{{ old('description', $category->description) }}</textarea>
                    </label>
                </div>
            </x-admin.section-card>

            <x-admin.section-card
                id="media"
                title="Category Image"
                description="This one image controls the homepage card/category card image. No extra media fields are shown here."
            >
                <div class="grid gap-5 md:grid-cols-[210px_minmax(0,1fr)] md:items-start">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        <template x-if="preview">
                            <img :src="preview" alt="Selected category thumbnail preview" class="aspect-square h-full w-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <div class="grid aspect-square place-items-center px-5 text-center text-sm font-bold text-slate-400">
                                Category image preview
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4">
                        <label class="admin-label">
                            Upload category image
                            <input
                                class="admin-input py-3"
                                type="file"
                                name="thumbnail_file"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                                x-on:change="previewImage($event)"
                            >
                            <small class="font-normal text-slate-500">JPG, PNG, WebP, or AVIF. Maximum 5 MB. A square image works best.</small>
                        </label>

                        <label class="admin-label">
                            Image alt text <span class="font-normal text-slate-400">(optional)</span>
                            <input
                                class="admin-input"
                                name="thumbnail_alt"
                                value="{{ old('thumbnail_alt', $category->thumbnail_alt) }}"
                                maxlength="255"
                                placeholder="Describe the category image"
                            >
                            <small class="font-normal text-slate-500">If empty, the category name is used automatically.</small>
                        </label>

                        @if($isEdit && ($category->thumbnail_path || $category->thumbnail_url))
                            <label class="flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm">
                                <input type="hidden" name="remove_thumbnail" value="0">
                                <input class="mt-1" type="checkbox" name="remove_thumbnail" value="1">
                                <span>
                                    <strong class="block text-red-800">Remove current image</strong>
                                    <small class="mt-1 block leading-5 text-red-700">Use this only when you want to clear the existing category image.</small>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>
            </x-admin.section-card>

            <div x-show="parentId === ''" x-transition>
            <x-admin.section-card
                title="Parent Category Icon"
                description="Upload a small image icon for top-level parent categories. This icon is used in storefront filters and the Shop Products menu."
            >
                <div class="grid gap-5 md:grid-cols-[104px_minmax(0,1fr)] md:items-start">
                    <div class="grid h-24 w-24 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <template x-if="iconPreview">
                            <img :src="iconPreview" alt="Selected parent category icon preview" class="h-full w-full object-contain">
                        </template>
                        <template x-if="!iconPreview">
                            <div class="text-center text-xs font-bold text-slate-400">Icon preview</div>
                        </template>
                    </div>

                    <div class="space-y-4">
                        <label class="admin-label">
                            Upload parent category icon
                            <input
                                class="admin-input py-3"
                                type="file"
                                name="icon_file"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                                x-on:change="previewIcon($event)"
                            >
                            <small class="font-normal text-slate-500">PNG, JPG, WebP, or AVIF. Transparent square icons work best. If empty, the default icon is shown automatically.</small>
                        </label>

                        <label class="admin-label">
                            Icon alt text <span class="font-normal text-slate-400">(optional)</span>
                            <input
                                class="admin-input"
                                name="icon_alt"
                                value="{{ old('icon_alt', $category->icon_alt) }}"
                                maxlength="255"
                                placeholder="Describe the parent category icon"
                            >
                        </label>

                        <label class="admin-label">
                            Icon image URL <span class="font-normal text-slate-400">(optional)</span>
                            <input
                                class="admin-input"
                                name="icon_url"
                                value="{{ old('icon_url', $category->icon_url) }}"
                                maxlength="2048"
                                placeholder="https://example.com/icon.svg"
                            >
                            <small class="font-normal text-slate-500">Uploading a file is recommended. URL is optional for externally hosted icons.</small>
                        </label>

                        @if($isEdit && ($category->icon_path || $category->icon_url))
                            <label class="flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm">
                                <input type="hidden" name="remove_icon" value="0">
                                <input class="mt-1" type="checkbox" name="remove_icon" value="1">
                                <span>
                                    <strong class="block text-red-800">Remove current icon</strong>
                                    <small class="mt-1 block leading-5 text-red-700">The default category icon will be used after saving.</small>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>
            </x-admin.section-card>
            </div>

            <x-admin.section-card
                id="display"
                title="Homepage / Storefront Display"
                description="Choose where this category appears. The featured option is only available for parent categories."
            >
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="is_visible_in_catalog" value="0">
                        <input class="mt-1" type="checkbox" name="is_visible_in_catalog" value="1" @checked(old('is_visible_in_catalog', $category->is_visible_in_catalog ?? true))>
                        <span>
                            <strong class="block text-sm">Show in catalog</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Make this category page available in the storefront.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="is_visible_in_menu" value="0">
                        <input class="mt-1" type="checkbox" name="is_visible_in_menu" value="1" @checked(old('is_visible_in_menu', $category->is_visible_in_menu ?? true))>
                        <span>
                            <strong class="block text-sm">Available to menus</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Allow navigation menus to link to it.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4" x-bind:class="parentId !== '' ? 'bg-slate-50 opacity-75' : ''">
                        <input type="hidden" name="is_featured" value="0">
                        <input class="mt-1" type="checkbox" name="is_featured" value="1" x-model="isFeatured" x-bind:disabled="parentId !== ''">
                        <span>
                            <strong class="block text-sm">Featured homepage category</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Controls the homepage “What are you looking for?” section.</small>
                            <small class="mt-1 block text-xs font-bold leading-5 text-amber-700" x-show="parentId !== ''">Only parent categories can be featured.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="show_product_count" value="0">
                        <input class="mt-1" type="checkbox" name="show_product_count" value="1" @checked(old('show_product_count', $category->show_product_count ?? true))>
                        <span>
                            <strong class="block text-sm">Show product count</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Display product totals on category cards.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="include_descendant_products" value="0">
                        <input class="mt-1" type="checkbox" name="include_descendant_products" value="1" @checked(old('include_descendant_products', $category->include_descendant_products ?? true))>
                        <span>
                            <strong class="block text-sm">Include child products</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Parent pages include products from their subcategories.</small>
                        </span>
                    </label>
                </div>
            </x-admin.section-card>

            <x-admin.section-card
                id="products"
                title="Products"
                description="Product assignment is kept separate so category editing stays clean."
            >
                @if($isEdit)
                    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="font-black text-slate-900">Manage products in this category</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Add, remove, sort, or feature products without cluttering this edit form.</p>
                        </div>
                        <a href="{{ route('admin.categories.products.index', $category) }}" class="btn btn-red">Manage Products</a>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <p class="font-bold text-slate-700">Save the category first, then you can assign products.</p>
                    </div>
                @endif
            </x-admin.section-card>

            <x-admin.section-card
                id="advanced"
                title="Advanced Settings"
                description="These options are hidden by default. Most categories do not need changes here."
            >
                <div class="space-y-4">
                    <details class="rounded-2xl border border-slate-200 bg-white p-4">
                        <summary class="cursor-pointer font-black text-slate-900">Page behavior</summary>
                        <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <label class="admin-label">
                                Page template
                                <select class="admin-input" name="page_template">
                                    @foreach([
                                        'product_grid' => 'Standard product grid',
                                        'sport_landing' => 'Sport landing page',
                                        'collection_landing' => 'Collection landing page',
                                        'image_focused' => 'Image-focused category',
                                        'quote_only' => 'Quote-only category',
                                        'content_landing' => 'Content landing page',
                                        'navigation_only' => 'Navigation-only category',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('page_template', $category->page_template ?? 'product_grid') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="admin-label">
                                Default product sorting
                                <select class="admin-input" name="default_product_sort">
                                    @foreach([
                                        'featured' => 'Featured',
                                        'newest' => 'Newest',
                                        'price-low' => 'Price: low to high',
                                        'price-high' => 'Price: high to low',
                                        'name-asc' => 'Name A–Z',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('default_product_sort', $category->default_product_sort ?? 'featured') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="admin-label">
                                Sort order
                                <input class="admin-input" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                            </label>

                            <label class="admin-label">
                                Publish date
                                <input class="admin-input" type="datetime-local" name="published_at" value="{{ old('published_at', optional($category->published_at)->format('Y-m-d\TH:i')) }}">
                            </label>

                            <label class="admin-label">
                                Eyebrow text <span class="font-normal text-slate-400">(optional)</span>
                                <input class="admin-input" name="eyebrow" value="{{ old('eyebrow', $category->eyebrow) }}" maxlength="160" placeholder="Most requested">
                            </label>

                            <label class="admin-label">
                                Short title <span class="font-normal text-slate-400">(optional)</span>
                                <input class="admin-input" name="short_title" value="{{ old('short_title', $category->short_title) }}" maxlength="160" placeholder="Short display title">
                            </label>

                            <label class="admin-label md:col-span-2 xl:col-span-3">
                                Best for <span class="font-normal text-slate-400">(optional)</span>
                                <textarea class="admin-textarea min-h-24" name="best_for" maxlength="5000" placeholder="Teams, schools, leagues, events...">{{ old('best_for', $category->best_for) }}</textarea>
                            </label>

                            <label class="admin-label md:col-span-2 xl:col-span-3">
                                Highlights <span class="font-normal text-slate-400">(optional)</span>
                                <textarea class="admin-textarea min-h-28" name="highlights_text" maxlength="5000" placeholder="One highlight per line">{{ old('highlights_text', collect($category->highlights ?? [])->implode("\n")) }}</textarea>
                            </label>
                        </div>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-4">
                        <summary class="cursor-pointer font-black text-slate-900">SEO basics</summary>
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <label class="admin-label">
                                SEO title
                                <input class="admin-input" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" maxlength="255">
                            </label>

                            <label class="admin-label md:col-span-2">
                                Meta description
                                <textarea class="admin-textarea" name="meta_description" maxlength="1000">{{ old('meta_description', $category->meta_description) }}</textarea>
                            </label>

                            <label class="admin-label md:col-span-2">
                                Meta keywords
                                <input class="admin-input" name="meta_keywords" value="{{ old('meta_keywords', $category->meta_keywords) }}" maxlength="2000">
                            </label>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                                <input type="hidden" name="robots_index" value="0">
                                <input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $category->robots_index ?? true))>
                                <span>
                                    <strong class="block">Allow indexing</strong>
                                    <small class="text-slate-500">Usually keep this enabled.</small>
                                </span>
                            </label>

                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                                <input type="hidden" name="robots_follow" value="0">
                                <input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow', $category->robots_follow ?? true))>
                                <span>
                                    <strong class="block">Allow link following</strong>
                                    <small class="text-slate-500">Usually keep this enabled.</small>
                                </span>
                            </label>
                        </div>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-4">
                        <summary class="cursor-pointer font-black text-slate-900">Product filters</summary>
                        <div class="mt-5 space-y-3">
                            @forelse($attributes as $attribute)
                                @php $pivot = $currentFilters->get($attribute->id)?->pivot; @endphp
                                <div class="grid items-end gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[1fr_1fr_110px_110px]">
                                    <label class="flex items-center gap-3 pb-3 text-sm font-bold">
                                        <input type="hidden" name="filter_settings[{{ $attribute->id }}][enabled]" value="0">
                                        <input type="checkbox" name="filter_settings[{{ $attribute->id }}][enabled]" value="1" @checked(old("filter_settings.{$attribute->id}.enabled", $pivot !== null))>
                                        {{ $attribute->name }}
                                    </label>
                                    <label class="admin-label">
                                        Customer label
                                        <input class="admin-input" name="filter_settings[{{ $attribute->id }}][label]" value="{{ old("filter_settings.{$attribute->id}.label", $pivot?->label) }}" placeholder="{{ $attribute->name }}">
                                    </label>
                                    <label class="admin-label">
                                        Order
                                        <input class="admin-input" type="number" min="0" name="filter_settings[{{ $attribute->id }}][sort_order]" value="{{ old("filter_settings.{$attribute->id}.sort_order", $pivot?->sort_order ?? $attribute->sort_order) }}">
                                    </label>
                                    <label class="flex items-center gap-2 pb-3 text-xs font-bold">
                                        <input type="hidden" name="filter_settings[{{ $attribute->id }}][is_expanded]" value="0">
                                        <input type="checkbox" name="filter_settings[{{ $attribute->id }}][is_expanded]" value="1" @checked(old("filter_settings.{$attribute->id}.is_expanded", $pivot?->is_expanded ?? true))>
                                        Expanded
                                    </label>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                                    <p class="text-slate-500">No catalog attributes exist yet.</p>
                                    <a href="{{ route('admin.attributes.create') }}" class="btn btn-red mt-4">Create Attribute</a>
                                </div>
                            @endforelse
                        </div>
                    </details>
                </div>
            </x-admin.section-card>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-24">
            <x-admin.section-card
                title="Publish Status"
                description="Control whether customers can see this category."
            >
                <label class="admin-label">
                    Status
                    <select class="admin-input" name="status">
                        @foreach(['draft', 'active', 'inactive', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $category->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>

                @if($isEdit && $category->status === 'active')
                    <a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener" class="btn btn-white mt-4 w-full">Preview Storefront</a>
                @endif
            </x-admin.section-card>

            <x-admin.section-card
                title="Clean Category Editor"
                description="The removed fields were advanced technical fields and extra media placements. The category image above now controls the important homepage/category card image."
            >
                <div class="space-y-3 text-sm leading-6 text-slate-600">
                    <p><strong class="text-slate-900">Slug:</strong> generated automatically from the name.</p>
                    <p><strong class="text-slate-900">Extra media:</strong> hidden from this form.</p>
                    <p><strong class="text-slate-900">Canonical URL/schema:</strong> hidden because they are technical SEO fields.</p>
                </div>
            </x-admin.section-card>
        </aside>
    </div>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-white">Cancel</a>
        @if($isEdit && $category->status === 'active')
            <a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener" class="btn btn-white">Preview Storefront</a>
        @endif
        <button class="btn btn-red">{{ $isEdit ? 'Update Category' : 'Create Category' }}</button>
    </div>
</form>

@once
<script>
function categoryAdminForm(initial) {
    return {
        name: initial.name || '',
        slug: initial.slug || '',
        preview: initial.preview || null,
        iconPreview: initial.iconPreview || null,
        parentId: initial.parentId || '',
        isFeatured: Boolean(initial.isFeatured),
        init() {
            if (!this.slug) this.updateSlug();
            if (this.parentId !== '') this.isFeatured = false;
            this.$watch('parentId', value => { if (value !== '') this.isFeatured = false; });
        },
        updateSlug() {
            this.slug = this.name
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
        previewImage(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            if (this.preview && this.preview.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
            this.preview = URL.createObjectURL(file);
        },
        previewIcon(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            if (this.iconPreview && this.iconPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.iconPreview);
            }
            this.iconPreview = URL.createObjectURL(file);
        },
    };
}
</script>
@endonce
