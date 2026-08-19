<div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h4 class="text-sm font-black text-brand-ink">Size extra charges</h4>
            <p class="mt-1 text-xs leading-5 text-slate-500">Enable this only when one or more sizes cost extra. Charges are applied per piece of that size.</p>
        </div>
        <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700">
            <input type="hidden" :name="`size_groups[${index}][has_size_extra_charges]`" value="0">
            <input type="checkbox" :name="`size_groups[${index}][has_size_extra_charges]`" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue" x-model="group.has_size_extra_charges" @change="toggleSizeExtraCharges(group)">
            Has extra charge
        </label>
    </div>

    <div x-show="group.has_size_extra_charges" x-cloak class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <template x-for="(size, sizeIndex) in (group.sizes || [])" :key="`${group.client_key || group.code || index}-size-${sizeIndex}`">
            <label class="rounded-xl border border-slate-200 bg-white p-3">
                <span class="block text-xs font-black text-slate-700" x-text="size"></span>
                <input type="hidden" :name="`size_groups[${index}][size_charges][${sizeIndex}][code]`" :value="sizeCode(group, size)">
                <input type="hidden" :name="`size_groups[${index}][size_charges][${sizeIndex}][label]`" :value="size">
                <span class="relative mt-2 block">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-black text-slate-400">$</span>
                    <input
                        type="number"
                        min="0"
                        max="999999999.99"
                        step="0.01"
                        inputmode="decimal"
                        class="admin-input mt-0"
                        style="padding-left: 2rem;"
                        :name="`size_groups[${index}][size_charges][${sizeIndex}][amount]`"
                        :value="sizeChargeValue(group, size)"
                        @input="setSizeChargeValue(group, size, $event.target.value)"
                        :aria-label="`Extra charge per piece for ${size}`"
                        :disabled="!group.has_size_extra_charges"
                        placeholder="0.00"
                    >
                </span>
            </label>
        </template>
    </div>
</div>
