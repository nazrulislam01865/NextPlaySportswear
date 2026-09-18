<x-admin.homepage.section-panel title="Shop By Sport" description="Choose the sports shown in the carousel, its default slide, and the four quick links over the image.">
    <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />
    <div class="mt-5" x-data="npHomepageItems(@js(old('items', $viewSection['items'])))">
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="np-home-admin__item-card rounded-2xl p-4">
                    <div class="mb-4 flex justify-between gap-3"><strong x-text="item.title || `Sport ${index + 1}`"></strong><button type="button" class="np-home-admin__danger" @click="remove(index)">Remove</button></div>
                    <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Sport category</span><select class="np-home-admin__input" :name="`items[${index}][category_id]`" x-model="item.category_id"><option value="">Select sport/category</option>@foreach($categoryOptions as $option)<option value="{{ $option['id'] }}">{{ $option['path'] ?: $option['display_label'] }}</option>@endforeach</select></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Title override</span><input class="np-home-admin__input" type="text" :name="`items[${index}][title]`" x-model="item.title"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">URL override</span><input class="np-home-admin__input" type="text" :name="`items[${index}][url]`" x-model="item.url"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Image alt text</span><input class="np-home-admin__input" type="text" :name="`items[${index}][image_alt]`" x-model="item.image_alt"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">Upload / replace image</span><input class="np-home-admin__input" type="file" :name="`items[${index}][image_file]`" accept="image/*,.webp,.avif"></label>
                        <label class="np-home-admin__field"><span class="np-home-admin__label">External image URL</span><input class="np-home-admin__input" type="text" :name="`items[${index}][image_url]`" x-model="item.image_url"></label>
                    </div>
                    <label class="mt-3 inline-flex items-center gap-2" x-show="item.image || item.image_path || item.image_url"><input type="checkbox" :name="`items[${index}][remove_image]`" value="1"> Remove current image</label>
                </div>
            </template>
        </div>
        <button type="button" class="np-home-admin__secondary mt-4" @click="add('sport')">+ Add Sport</button>
    </div>
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <label class="np-home-admin__field"><span class="np-home-admin__label">Default sport</span><select name="settings[default_sport_id]" class="np-home-admin__input"><option value="">First configured sport</option>@foreach($categoryOptions as $option)<option value="{{ $option['id'] }}" @selected((string)old('settings.default_sport_id', $viewSection['settings']['default_sport_id'] ?? '') === (string)$option['id'])>{{ $option['path'] ?: $option['display_label'] }}</option>@endforeach</select></label>
        <div></div>
        @foreach(($viewSection['settings']['quick_links'] ?? []) as $index => $link)
            <div class="np-home-admin__item-card rounded-2xl p-4">
                <input type="hidden" name="settings[quick_links][{{ $index }}][id]" value="{{ $link['id'] }}">
                <x-admin.homepage.text-field name="settings[quick_links][{{ $index }}][label]" old-name="settings.quick_links.{{ $index }}.label" label="Quick link label" :value="$link['label']" />
                <div class="mt-3"><x-admin.homepage.link-field name="settings[quick_links][{{ $index }}][url]" old-name="settings.quick_links.{{ $index }}.url" label="Quick link URL" :value="$link['url']" /></div>
            </div>
        @endforeach
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
