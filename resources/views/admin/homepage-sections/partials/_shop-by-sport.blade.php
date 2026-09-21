@php($aspect = \App\Support\HomepageImageAspectRatios::forSectionItem('shop_by_sport'))
<x-admin.homepage.section-panel
    title="Shop By Sport"
    description="Manage each sport slide, its background image, and the buttons shown for that sport. New sports start with four editable buttons, and admins can add or remove buttons freely."
>
    <x-admin.homepage.text-field name="title" label="Section heading" :value="$viewSection['title']" />

    <div class="mt-5" x-data="npHomepageItems(@js(old('items', $viewSection['items'])), @js($definition['item_button_defaults'] ?? []))">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold">Sport slides & background images</h3>
                <p class="np-home-admin__muted mt-1 text-sm">Each sport is one slide. Configure its image, title, destination, and its own optional buttons below.</p>
            </div>
        </div>

        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="np-home-admin__item-card rounded-2xl p-4">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <strong x-text="item.title || `Sport ${index + 1}`"></strong>
                            <p class="np-home-admin__muted mt-1 text-xs">Carousel slide</p>
                        </div>
                        <button type="button" class="np-home-admin__danger" @click="remove(index)">Remove</button>
                    </div>

                    <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">
                    <input type="hidden" :name="`items[${index}][image_upload_token]`" x-model="item.image_upload_token">
                    <input type="hidden" :name="`items[${index}][buttons_configured]`" value="1">

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="np-home-admin__field">
                            <span class="np-home-admin__label">Sport category</span>
                            <select class="np-home-admin__input" :name="`items[${index}][category_id]`" x-model="item.category_id">
                                <option value="">Select sport/category</option>
                                @foreach($categoryOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['path'] ?: $option['display_label'] }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="np-home-admin__field">
                            <span class="np-home-admin__label">Slide title override</span>
                            <input class="np-home-admin__input" type="text" :name="`items[${index}][title]`" x-model="item.title" placeholder="Uses the sport name when empty">
                        </label>

                        <label class="np-home-admin__field md:col-span-2">
                            <span class="np-home-admin__label">Slide title destination URL</span>
                            <input class="np-home-admin__input" type="text" :name="`items[${index}][url]`" x-model="item.url" placeholder="Uses the selected category URL when empty">
                        </label>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white/60 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <strong>Background image</strong>
                                <p class="np-home-admin__muted mt-1 text-xs">This image fills the large Shop By Sport banner for this slide.</p>
                            </div>
                        </div>

                        <template x-if="item.image">
                            <img :src="item.image" alt="" class="np-home-admin__media-preview mb-3 h-40 w-full rounded-xl object-cover">
                        </template>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="np-home-admin__field">
                                <span class="np-home-admin__label">Upload / replace background</span>
                                <input class="np-home-admin__input" type="file" :name="`items[${index}][image_file]`" accept=".jpg,.jpeg,.png,.webp,.avif,image/*">
                                <span class="mt-1.5 block text-xs leading-5 text-slate-500"><strong>Target aspect ratio: {{ $aspect['ratio'] }}</strong> (±{{ (int) round($aspect['tolerance'] * 100) }}% accepted). Resolution is flexible.</span>
                            </label>

                            <label class="np-home-admin__field">
                                <span class="np-home-admin__label">External background image URL</span>
                                <input class="np-home-admin__input" type="text" :name="`items[${index}][image_url]`" x-model="item.image_url">
                            </label>

                            <label class="np-home-admin__field md:col-span-2">
                                <span class="np-home-admin__label">Background image alt text</span>
                                <input class="np-home-admin__input" type="text" :name="`items[${index}][image_alt]`" x-model="item.image_alt">
                            </label>
                        </div>

                        <label class="mt-3 inline-flex items-center gap-2 text-sm" x-show="item.image || item.image_path || item.image_url">
                            <input type="checkbox" :name="`items[${index}][remove_image]`" value="1">
                            Remove current background image
                        </label>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>Buttons for this sport</strong>
                                <p class="np-home-admin__muted mt-1 text-xs">Each new sport starts with four editable buttons. Change their text or links, add more buttons, or remove any/all of them.</p>
                            </div>
                            <button type="button" class="np-home-admin__secondary" @click="addButton(item)">+ Add Button</button>
                        </div>

                        <template x-if="!Array.isArray(item.buttons) || item.buttons.length === 0">
                            <p class="np-home-admin__muted mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm">No buttons configured for this sport.</p>
                        </template>

                        <div class="mt-4 space-y-3" x-show="Array.isArray(item.buttons) && item.buttons.length > 0">
                            <template x-for="(button, buttonIndex) in item.buttons" :key="button.id || `${item.id}-button-${buttonIndex}`">
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <input type="hidden" :name="`items[${index}][buttons][${buttonIndex}][id]`" x-model="button.id">

                                    <div class="grid gap-4 md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)_auto] md:items-end">
                                        <label class="np-home-admin__field">
                                            <span class="np-home-admin__label">Button text</span>
                                            <input class="np-home-admin__input" type="text" :name="`items[${index}][buttons][${buttonIndex}][label]`" x-model="button.label" placeholder="e.g. JERSEY">
                                        </label>

                                        <label class="np-home-admin__field">
                                            <span class="np-home-admin__label">Button link</span>
                                            <input class="np-home-admin__input" type="text" :name="`items[${index}][buttons][${buttonIndex}][url]`" x-model="button.url" placeholder="e.g. /products?q=jersey">
                                        </label>

                                        <button type="button" class="np-home-admin__danger mb-1" @click="removeButton(item, buttonIndex)">Remove</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" class="np-home-admin__secondary mt-4" @click="add('sport')">+ Add Sport</button>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <label class="np-home-admin__field">
            <span class="np-home-admin__label">Default sport</span>
            <select name="settings[default_sport_id]" class="np-home-admin__input">
                <option value="">First configured sport</option>
                @foreach($categoryOptions as $option)
                    <option value="{{ $option['id'] }}" @selected((string)old('settings.default_sport_id', $viewSection['settings']['default_sport_id'] ?? '') === (string)$option['id'])>{{ $option['path'] ?: $option['display_label'] }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
