@php($aspect = \App\Support\HomepageImageAspectRatios::forSectionItem('shop_by_category'))
<x-admin.homepage.section-panel title="Shop By Category" description="Choose the category tiles used by the new homepage. You can override each title, URL and image.">
    <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />
    <div class="mt-5" x-data="npHomepageItems(@js(old('items', $viewSection['items'])))">
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="np-home-admin__item-card rounded-2xl p-4">
                    <div class="mb-4 flex justify-between gap-3"><strong x-text="item.title || `Category tile ${index + 1}`"></strong><button type="button" class="np-home-admin__danger" @click="remove(index)">Remove</button></div>
                    <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">
                    <input type="hidden" :name="`items[${index}][image_upload_token]`" x-model="item.image_upload_token">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Catalog category</span><select class="np-home-admin__input" :name="`items[${index}][category_id]`" x-model="item.category_id"><option value="">Select category</option>@foreach($categoryOptions as $option)<option value="{{ $option['id'] }}">{{ $option['path'] ?: $option['display_label'] }}</option>@endforeach</select></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Title override</span><input class="np-home-admin__input" type="text" :name="`items[${index}][title]`" x-model="item.title"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">URL override</span><input class="np-home-admin__input" type="text" :name="`items[${index}][url]`" x-model="item.url"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Image alt text</span><input class="np-home-admin__input" type="text" :name="`items[${index}][image_alt]`" x-model="item.image_alt"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Upload / replace image</span><input class="np-home-admin__input" type="file" :name="`items[${index}][image_file]`" accept="image/*,.webp,.avif"><span class="mt-1.5 block text-xs leading-5 text-slate-500"><strong>Target aspect ratio: {{ $aspect['ratio'] }}</strong> (±{{ (int) round($aspect['tolerance'] * 100) }}% accepted). Resolution is flexible.</span></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">External image URL</span><input class="np-home-admin__input" type="text" :name="`items[${index}][image_url]`" x-model="item.image_url"></label>
                    </div>
                    <label class="mt-3 inline-flex items-center gap-2" x-show="item.image || item.image_path || item.image_url"><input type="checkbox" :name="`items[${index}][remove_image]`" value="1"> Remove current image</label>
                </div>
            </template>
        </div>
        <button type="button" class="np-home-admin__secondary mt-4" @click="add('category')">+ Add Category Tile</button>
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
