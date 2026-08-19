<article class="np-nested-card">
    <input type="hidden" :name="`size_groups[${index}][existing_id]`" :value="group.existing_id || ''">
    <input type="hidden" :name="`size_groups[${index}][size_option_group_id]`" :value="group.size_option_group_id || ''">
    <input type="hidden" :name="`size_groups[${index}][name]`" :value="group.name">
    <input type="hidden" :name="`size_groups[${index}][code]`" :value="group.code">
    <input type="hidden" :name="`size_groups[${index}][description_html]`" :value="group.description_html || ''">
    <input type="hidden" :name="`size_groups[${index}][chart_html]`" :value="group.chart_html || ''">
    <input type="hidden" :name="`size_groups[${index}][chart_title]`" :value="group.chart_title || ''">
    <input type="hidden" :name="`size_groups[${index}][chart_note]`" :value="group.chart_note || ''">
    <input type="hidden" :name="`size_groups[${index}][chart_image_url]`" :value="group.chart_image_url || ''">
    <input type="hidden" :name="`size_groups[${index}][chart_columns_text]`" :value="(group.chart_columns || []).join(', ')">
    <input type="hidden" :name="`size_groups[${index}][chart_rows_text]`" :value="(group.chart_rows || []).map(row => row.join(' | ')).join('\n')">
    <input type="hidden" :name="`size_groups[${index}][clear_chart_image]`" value="0">
    <input type="hidden" :name="`size_groups[${index}][chart_enabled]`" :value="group.chart_enabled ? 1 : 0">
    <input type="hidden" :name="`size_groups[${index}][sizes_text]`" :value="(group.sizes || []).join(', ')">
    <input type="hidden" :name="`size_groups[${index}][is_active]`" value="1">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue" x-text="group.audience_label || 'Size Option'"></p>
            <h3 class="mt-1 text-lg font-black text-brand-ink" x-text="group.name"></h3>
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="size in (group.sizes || [])" :key="size">
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-700" x-text="size"></span>
                </template>
            </div>
        </div>
        <div class="flex shrink-0 items-start gap-3">
            <img x-show="group.chart_image_preview" :src="group.chart_image_preview" :alt="`${group.name} size chart`" class="h-20 w-20 rounded-xl border border-slate-200 bg-slate-50 object-contain">
            <button type="button" class="np-danger-button" @click="sizeGroups.splice(index,1)">Remove</button>
        </div>
    </div>

    <x-admin.size-extra-charge-fields />
</article>
