@php
    $initialName = old('name', '');
    $initialPreview = null;
    $initialParentId = (string) old('parent_id', $category->parent_id ?? '');
    $initialIsFeatured = filter_var(old('is_featured', false), FILTER_VALIDATE_BOOLEAN);
@endphp

<form
    method="POST"
    enctype="multipart/form-data"
    action="{{ route('admin.categories.store') }}"
    class="space-y-6"
    x-data="categoryCreateForm(@js(['name' => $initialName, 'preview' => $initialPreview, 'parentId' => $initialParentId, 'isFeatured' => $initialIsFeatured]))"
>
    @csrf

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800" role="alert">
            <strong class="block font-black">Please correct the following fields:</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <x-admin.section-card
                title="Add New Category"
                description="Create the category with only the essential storefront information. Only the most important fields are shown. The slug is generated automatically from the category name."
            >
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="admin-label md:col-span-2">
                        Category name
                        <input
                            class="admin-input"
                            name="name"
                            x-model="name"
                            x-on:input="updateSlug()"
                            value="{{ $initialName }}"
                            required
                            maxlength="160"
                            autocomplete="off"
                            placeholder="Example: Baseball Jerseys"
                        >
                        <small class="font-normal text-slate-500">This is the category title customers will see.</small>
                    </label>

                    <div class="md:col-span-2 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-blue">Auto URL</p>
                        <p class="mt-2 break-all font-mono text-sm font-bold text-slate-800">
                            /category/<span x-text="slug || 'category-name'"></span>
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">The slug is generated automatically from the category name. If it already exists, the system adds a number.</p>
                    </div>

                    <label class="admin-label">
                        Parent category
                        <select class="admin-input" name="parent_id" x-model="parentId">
                            <option value="">None — create as a parent category</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>
                                    {{ $parent->indented_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="font-normal text-slate-500">When no parent is selected, this becomes a top-level parent category automatically.</small>
                    </label>

                    <label class="admin-label">
                        Display type
                        <select class="admin-input" name="display_mode">
                            <option value="default" @selected(old('display_mode', 'default') === 'default')>Default — products and child categories</option>
                            <option value="subcategories" @selected(old('display_mode') === 'subcategories')>Child categories only</option>
                            <option value="content" @selected(old('display_mode') === 'content')>Content landing page</option>
                        </select>
                        <small class="font-normal text-slate-500">Choose the simple storefront layout. It can be changed later.</small>
                    </label>

                    <label class="admin-label md:col-span-2">
                        Description
                        <textarea
                            class="admin-textarea min-h-40"
                            name="description"
                            maxlength="10000"
                            placeholder="A short description of this category..."
                        >{{ old('description') }}</textarea>
                        <small class="font-normal text-slate-500">Used on the category page and as the default search description.</small>
                    </label>
                </div>
            </x-admin.section-card>

            <x-admin.section-card
                title="Category Thumbnail"
                description="Upload the main image used on category cards. This image is also used on homepage category sections, including Popular Custom Sportswear, when the category is a sport category or under a sport category."
            >
                <div class="grid gap-5 md:grid-cols-[190px_minmax(0,1fr)] md:items-start">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        <template x-if="preview">
                            <img :src="preview" alt="Selected category thumbnail preview" class="aspect-square h-full w-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <div class="grid aspect-square place-items-center px-5 text-center text-sm font-bold text-slate-400">
                                Thumbnail preview
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4">
                        <label class="admin-label">
                            Upload image
                            <input
                                class="admin-input py-3"
                                type="file"
                                name="thumbnail_file"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                                x-on:change="previewImage($event)"
                            >
                            <small class="font-normal text-slate-500">JPG, PNG, WebP, or AVIF. Maximum 5 MB.</small>
                        </label>

                        <label class="admin-label">
                            Image alt text <span class="font-normal text-slate-400">(optional)</span>
                            <input
                                class="admin-input"
                                name="thumbnail_alt"
                                value="{{ old('thumbnail_alt') }}"
                                maxlength="255"
                                placeholder="Describe the category image"
                            >
                            <small class="font-normal text-slate-500">If empty, the category name is used automatically.</small>
                        </label>
                    </div>
                </div>
            </x-admin.section-card>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-24">
            <x-admin.section-card
                title="Where Should It Appear?"
                description="Choose the storefront placements for this category."
            >
                <div class="space-y-3">
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="is_visible_in_catalog" value="0">
                        <input class="mt-1" type="checkbox" name="is_visible_in_catalog" value="1" @checked(old('is_visible_in_catalog', true))>
                        <span>
                            <strong class="block text-sm">Categories page</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Make this category and its public page available in the storefront catalog.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" name="is_visible_in_menu" value="0">
                        <input class="mt-1" type="checkbox" name="is_visible_in_menu" value="1" @checked(old('is_visible_in_menu', true))>
                        <span>
                            <strong class="block text-sm">Navigation menus</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Allow this category to be selected in header, footer, and other navigation menus.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4" x-bind:class="parentId !== '' ? 'bg-slate-50 opacity-75' : ''">
                        <input type="hidden" name="is_featured" value="0">
                        <input class="mt-1" type="checkbox" name="is_featured" value="1" x-model="isFeatured" x-bind:disabled="parentId !== ''">
                        <span>
                            <strong class="block text-sm">Featured homepage category</strong>
                            <small class="mt-1 block text-xs leading-5 text-slate-500">Only top-level parent categories can be selected for the homepage “What are you looking for?” section.</small>
                            <small class="mt-1 block text-xs font-bold leading-5 text-amber-700" x-show="parentId !== ''">Select “None — create as a parent category” above to make this category eligible.</small>
                        </span>
                    </label>

                </div>
            </x-admin.section-card>

            <x-admin.section-card
                title="Publish"
                description="Save it publicly now or keep it as a draft for later editing."
            >
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                    <input type="hidden" name="publish_now" value="0">
                    <input class="mt-1" type="checkbox" name="publish_now" value="1" @checked(old('publish_now', true))>
                    <span>
                        <strong class="block text-sm">Publish immediately</strong>
                        <small class="mt-1 block text-xs leading-5 text-slate-500">Uncheck this to save the new category as a private draft.</small>
                    </span>
                </label>

                <div class="mt-4 rounded-2xl bg-blue-50 p-4 text-xs leading-5 text-slate-600">
                    Slug is automatic. Extra media, custom schema, and canonical URL fields are not needed in the simplified category editor.
                </div>
            </x-admin.section-card>
        </aside>
    </div>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-white">Cancel</a>
        <button class="btn btn-red">Add New Category</button>
    </div>
</form>

@once
<script>
function categoryCreateForm(initial) {
    return {
        name: initial.name || '',
        slug: '',
        preview: initial.preview || null,
        parentId: initial.parentId || '',
        isFeatured: Boolean(initial.isFeatured),
        init() {
            this.updateSlug();
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
            if (!file) {
                this.preview = null;
                return;
            }
            if (this.preview && this.preview.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
            this.preview = URL.createObjectURL(file);
        },
    };
}
</script>
@endonce
