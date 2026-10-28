@props(['product'])

<div id="item-personalization" class="grid gap-3">
    <div class="np-option-tiles">
        <article>
            <span class="np-option-tiles__icon teal">♙</span>
            <div>
                <strong>Item Personalization</strong>
                <small>Select the personalization templates customers can fill in for this product.</small>
            </div>
            <button type="button" class="np-secondary-button" @click="openPersonalizationPicker()">＋ Add template</button>
        </article>
    </div>

    <input type="hidden" name="jersey_roster_enabled" :value="personalizationTemplateIds.length ? 1 : 0">
    <input type="hidden" name="jersey_roster_optional" value="0">
    <template x-for="id in personalizationTemplateIds" :key="id">
        <input type="hidden" name="personalization_template_ids[]" :value="id">
    </template>

    <details class="np-config-panel" open x-show="personalizationTemplateIds.length > 0" x-cloak>
        <summary>Item personalization</summary>
        <div class="mt-4 space-y-4">
            <template x-for="id in personalizationTemplateIds" :key="id">
                <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="min-w-0">
                        <h3 class="font-black text-brand-ink" x-text="personalizationTemplateById(id)?.section_title || 'Personalization template'"></h3>
                        <p class="mt-1 text-xs font-bold text-brand-blue" x-text="personalizationTemplateById(id)?.name || ''"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="personalizationTemplateById(id)?.category_label || ''"></p>
                        <template x-for="colorField in (personalizationTemplateById(id)?.color_fields || [])" :key="colorField.label">
                            <div class="mt-2">
                                <small class="text-xs text-slate-500" x-text="colorField.label"></small>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <template x-for="color in (colorField.colors || [])" :key="color.hex || color">
                                        <span class="inline-block h-7 w-7 rounded-full border border-slate-300" :style="`background:${color.hex || color}`" :title="color.name || color.hex || color"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <p class="mt-1 text-xs text-slate-500" x-show="personalizationTemplateById(id) && !personalizationTemplateById(id).available">Unavailable in Master Data — remove or activate before saving.</p>
                    </div>
                    <button type="button" class="np-danger-button" @click="removePersonalizationTemplate(id)">Remove</button>
                </article>
            </template>
        </div>
    </details>
    @error('personalization_template_ids')<small class="text-red-600">{{ $message }}</small>@enderror
    @error('personalization_template_ids.*')<small class="text-red-600">{{ $message }}</small>@enderror
</div>
