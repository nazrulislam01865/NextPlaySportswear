<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][existing_id]`" :value="value.existing_id || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][jersey_customization_option_id]`" :value="value.jersey_customization_option_id || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][label]`" :value="value.label">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][code]`" :value="value.code">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][description]`" :value="value.description || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][color_hex]`" :value="value.color_hex || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][image_url]`" :value="value.image_url || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][clear_images]`" value="0">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_active]`" value="1">

    <div class="p-4 sm:p-5">
        <div class="flex min-w-0 items-start gap-4">
            <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100 sm:h-24 sm:w-24">
                <img
                    x-show="value.primary_image_url"
                    :src="value.primary_image_url"
                    :alt="value.label"
                    class="h-full w-full object-cover"
                >
                <span
                    x-show="!value.primary_image_url && value.color_hex"
                    class="h-12 w-12 rounded-full border border-slate-300"
                    :style="`background:${value.color_hex}`"
                ></span>
                <span
                    x-show="!value.primary_image_url && !value.color_hex"
                    class="px-2 text-center text-[10px] font-black uppercase tracking-wide text-slate-400"
                >No image</span>
            </div>

            <div class="min-w-0 flex-1 pt-1">
                <h5 class="text-base font-black text-brand-ink sm:text-lg" x-text="value.label"></h5>
                <p
                    x-show="value.description"
                    class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500 sm:text-sm"
                    x-text="value.description"
                ></p>
                <span
                    x-show="value.color_hex"
                    class="mt-2 inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[10px] font-bold text-slate-600"
                    x-text="value.color_hex"
                ></span>
            </div>
        </div>

        <div class="mt-5 space-y-4 border-t border-slate-200 pt-5">
            <div class="grid gap-2 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center sm:gap-5">
                <label class="text-sm font-black text-slate-700" :for="`option-charge-${gIndex}-${vIndex}`">Additional charge</label>
                <input
                    class="admin-input !mt-0"
                    type="number"
                    min="0"
                    step="0.01"
                    :id="`option-charge-${gIndex}-${vIndex}`"
                    :name="`option_groups[${gIndex}][values][${vIndex}][price_adjustment]`"
                    x-model="value.price_adjustment"
                >
            </div>

            <div class="grid gap-2 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center sm:gap-5">
                <label class="text-sm font-black text-slate-700" :for="`option-charge-basis-${gIndex}-${vIndex}`">Charge basis</label>
                <select
                    class="admin-input !mt-0"
                    :id="`option-charge-basis-${gIndex}-${vIndex}`"
                    :name="`option_groups[${gIndex}][values][${vIndex}][charge_type]`"
                    x-model="value.charge_type"
                >
                    <option value="included">Included / no charge</option>
                    <option value="per_unit">Per piece</option>
                    <option value="fixed_order">Fixed per order</option>
                </select>
            </div>

            <div class="grid gap-2 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center sm:gap-5">
                <span class="text-sm font-black text-slate-700">Default choice</span>
                <div>
                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_default]`" :value="value.is_default ? 1 : 0">
                    <button
                        type="button"
                        class="btn w-full sm:w-auto"
                        :class="value.is_default ? 'btn-navy' : 'btn-white'"
                        @click="setDefaultValue(group,vIndex)"
                        x-text="value.is_default ? 'Default choice' : 'Make default'"
                    ></button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
        <button
            type="button"
            class="text-xs font-black text-red-700 transition hover:text-red-800"
            @click="removeOptionValue(group,vIndex)"
        >Remove item</button>
    </div>
</div>
