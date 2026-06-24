@props([
    'name' => 'images',
    'images' => [],
    'title' => 'Images',
    'description' => 'Add one or multiple uploaded images or secure image links. Select one primary image.',
    'compact' => false,
])

<div x-data="adminImageCollection(@js($images), @js($name))">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-base font-black text-brand-ink">{{ $title }}</h3>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
        </div>
        <button type="button" class="btn btn-white shrink-0" @click="addImage()">+ Add Image</button>
    </div>

    <div
        x-show="images.length === 0"
        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-7 text-center"
    >
        <p class="font-bold text-slate-700">No image added</p>
        <p class="mt-1 text-sm text-slate-500">Images are optional.</p>
    </div>

    <div class="space-y-3">
        <template x-for="(image, index) in images" :key="image.key">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <input type="hidden" :name="`${fieldName}[${index}][existing_id]`" x-model="image.existing_id">
                <input type="hidden" :name="`${fieldName}[${index}][is_primary]`" :value="primaryKey === image.key ? 1 : 0">
                <input type="hidden" :name="`${fieldName}[${index}][sort_order]`" :value="index">

                @if($compact)
                    <div class="grid gap-4 md:grid-cols-[88px_minmax(0,1fr)]">
                        <div class="h-[88px] w-[88px] overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <img
                                x-show="image.preview || image.image_url"
                                :src="image.preview || image.image_url"
                                :alt="image.name || 'Image preview'"
                                class="h-full w-full object-cover"
                            >
                            <div
                                x-show="! image.preview && ! image.image_url"
                                class="grid h-full place-items-center px-2 text-center text-[11px] font-bold leading-4 text-slate-400"
                            >
                                Thumbnail
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="grid gap-3 lg:grid-cols-2">
                                <label class="admin-label">
                                    Image Name
                                    <input
                                        class="admin-input"
                                        type="text"
                                        :name="`${fieldName}[${index}][name]`"
                                        x-model="image.name"
                                        maxlength="180"
                                        placeholder="Example: Front view"
                                    >
                                </label>

                                <label class="admin-label">
                                    Image Link
                                    <input
                                        class="admin-input"
                                        type="url"
                                        :name="`${fieldName}[${index}][image_url]`"
                                        x-model.trim="image.image_url"
                                        placeholder="https://example.com/image.jpg"
                                    >
                                </label>
                            </div>

                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
                                <label class="admin-label">
                                    Upload Image
                                    <input
                                        class="admin-input h-auto py-2.5"
                                        type="file"
                                        :name="`${fieldName}[${index}][image_file]`"
                                        accept="image/jpeg,image/png,image/webp,image/avif"
                                        @change="selectFile(image, $event)"
                                    >
                                    <span class="mt-1 block text-xs font-medium text-slate-500">
                                        JPG, PNG, WebP or AVIF. Maximum 5 MB.
                                    </span>
                                </label>

                                <label class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-bold text-slate-700">
                                    <input
                                        type="radio"
                                        :checked="primaryKey === image.key"
                                        @change="primaryKey = image.key"
                                    >
                                    Primary
                                </label>

                                <button
                                    type="button"
                                    class="btn btn-white text-red-700"
                                    @click="removeImage(index)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)]">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <div class="aspect-square">
                                <img
                                    x-show="image.preview || image.image_url"
                                    :src="image.preview || image.image_url"
                                    :alt="image.name || 'Image preview'"
                                    class="h-full w-full object-cover"
                                >
                                <div
                                    x-show="! image.preview && ! image.image_url"
                                    class="grid h-full place-items-center px-4 text-center text-xs font-bold text-slate-400"
                                >
                                    Image preview
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="admin-label">
                                Name
                                <input
                                    class="admin-input"
                                    type="text"
                                    :name="`${fieldName}[${index}][name]`"
                                    x-model="image.name"
                                    maxlength="180"
                                    placeholder="Example: Front view"
                                >
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="admin-label">
                                    Upload Image
                                    <input
                                        class="admin-input h-auto py-3"
                                        type="file"
                                        :name="`${fieldName}[${index}][image_file]`"
                                        accept="image/jpeg,image/png,image/webp,image/avif"
                                        @change="selectFile(image, $event)"
                                    >
                                    <span class="mt-1 block text-xs font-medium text-slate-500">
                                        JPG, PNG, WebP or AVIF. Maximum 5 MB.
                                    </span>
                                </label>

                                <label class="admin-label">
                                    Image Link
                                    <input
                                        class="admin-input"
                                        type="url"
                                        :name="`${fieldName}[${index}][image_url]`"
                                        x-model.trim="image.image_url"
                                        placeholder="https://example.com/image.jpg"
                                    >
                                </label>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <label class="inline-flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700">
                                    <input
                                        type="radio"
                                        :checked="primaryKey === image.key"
                                        @change="primaryKey = image.key"
                                    >
                                    Primary image
                                </label>
                                <button
                                    type="button"
                                    class="btn btn-white text-red-700"
                                    @click="removeImage(index)"
                                >
                                    Remove Image
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </article>
        </template>
    </div>
</div>

@once
    <script>
        function adminImageCollection(initialImages, fieldName) {
            const normalized = (initialImages || []).map((image, index) => ({
                key: image.key || (image.existing_id ? `existing-${image.existing_id}` : `initial-${index}`),
                existing_id: image.existing_id || '',
                name: image.name || '',
                image_url: image.image_url || '',
                preview: image.preview || '',
                is_primary: Boolean(image.is_primary),
            }));

            return {
                fieldName,
                images: normalized,
                primaryKey: normalized.find(image => image.is_primary)?.key || normalized[0]?.key || null,
                addImage() {
                    const key = `new-${Date.now()}-${Math.random().toString(36).slice(2)}`;

                    this.images.push({
                        key,
                        existing_id: '',
                        name: '',
                        image_url: '',
                        preview: '',
                        is_primary: false,
                    });

                    if (! this.primaryKey) {
                        this.primaryKey = key;
                    }
                },
                removeImage(index) {
                    const removed = this.images[index];

                    if (removed?.preview?.startsWith('blob:')) {
                        URL.revokeObjectURL(removed.preview);
                    }

                    this.images.splice(index, 1);

                    if (removed?.key === this.primaryKey) {
                        this.primaryKey = this.images[0]?.key || null;
                    }
                },
                selectFile(image, event) {
                    const file = event.target.files?.[0];

                    if (! file) {
                        return;
                    }

                    if (image.preview?.startsWith('blob:')) {
                        URL.revokeObjectURL(image.preview);
                    }

                    image.preview = URL.createObjectURL(file);
                    image.image_url = '';

                    if (! image.name) {
                        image.name = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
                    }
                },
            };
        }
    </script>
@endonce
