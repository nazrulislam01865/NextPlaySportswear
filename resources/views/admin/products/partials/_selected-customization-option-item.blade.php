<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][existing_id]`" :value="value.existing_id || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][jersey_customization_option_id]`" :value="value.jersey_customization_option_id || ''">
    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][world_cup_customization_option_id]`" :value="value.world_cup_customization_option_id || ''">
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
            <x-admin.customization-extra-charge />

            <div
                x-show="isFabricGroup(group)"
                x-init="ensureFabricPricing(value, group)"
                class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4"
            >
                <template x-if="value.fabric_price_table">
                    <div class="space-y-4">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][fabric_key]`" :value="fabricPriceKey(value)">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][jersey_customization_option_id]`" :value="value.jersey_customization_option_id || ''">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][fabric_code]`" :value="value.code || ''">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][fabric_label]`" :value="value.label || ''">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][has_custom_pricing]`" :value="value.fabric_price_table.has_custom_pricing ? 1 : 0">
                        <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_highlight_column]`" :value="value.fabric_price_table.highlight_column || 1">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-brand-ink">Fabric-specific price table</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">
                                    Enable this only when this fabric needs its own quantity pricing. If disabled, the product default price table will be used.
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-blue-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-sm">
                                    <input class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" type="checkbox" x-model="value.fabric_price_table.has_custom_pricing">
                                    Different price
                                </label>
                                <label
                                    x-show="value.fabric_price_table.has_custom_pricing"
                                    x-cloak
                                    class="np-import-button !rounded-full !px-3 !py-2 text-xs"
                                    :class="isImportingFabricTable(value.fabric_price_table) ? 'pointer-events-none opacity-70' : ''"
                                >
                                    <input type="file" accept=".xlsx,.csv" @change="importFabricPriceTable($event, value.fabric_price_table, value.label, fabricPriceKey(value), gIndex, vIndex)">
                                    <span x-text="isImportingFabricTable(value.fabric_price_table) ? 'Importing…' : 'Import Excel'"></span>
                                </label>
                            </div>
                        </div>

                        <p x-show="value.fabric_price_table.import_status" x-cloak class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700" x-text="value.fabric_price_table.import_status"></p>
                        <p x-show="value.fabric_price_table.import_error" x-cloak class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-700" x-text="value.fabric_price_table.import_error"></p>

                        <div x-show="value.fabric_price_table.has_custom_pricing" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div>
                                <label class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Fabric pricing note</label>
                                <textarea
                                    class="admin-input mt-2"
                                    rows="2"
                                    :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_note]`"
                                    x-model="value.fabric_price_table.note"
                                    placeholder="Optional note shown with this fabric price table"
                                ></textarea>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_headers][0]`" value="Quantity">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-3 text-left">Quantity</th>
                                            <template x-for="(header, headerIndex) in value.fabric_price_table.headers" :key="`fabric-header-${gIndex}-${vIndex}-${headerIndex}`">
                                                <th class="min-w-[170px] px-3 py-3 text-left">
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            class="admin-input !mt-0 h-9 text-xs"
                                                            :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_headers][${headerIndex + 1}]`"
                                                            x-model="value.fabric_price_table.headers[headerIndex]"
                                                            @input="normalizeFabricPriceTable(value.fabric_price_table)"
                                                        >
                                                        <button type="button" class="text-slate-400 hover:text-red-600" @click="removeFabricPriceHeader(value.fabric_price_table, headerIndex)" x-show="value.fabric_price_table.headers.length > 1">×</button>
                                                    </div>
                                                </th>
                                            </template>
                                            <th class="w-10 px-3 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="(row, rowIndex) in value.fabric_price_table.rows" :key="`fabric-row-${gIndex}-${vIndex}-${rowIndex}`">
                                            <tr>
                                                <td class="min-w-[210px] px-3 py-3 align-top">
                                                    <input type="hidden" :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_rows][${rowIndex}][0]`" :value="quantityRangeLabel(row)">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">
                                                            Min
                                                            <input
                                                                class="admin-input !mt-1 h-10"
                                                                type="number"
                                                                min="1"
                                                                :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_ranges][${rowIndex}][minimum_quantity]`"
                                                                x-model="row.minimum_quantity"
                                                                @input="recalculateFabricPriceMaximums(value.fabric_price_table)"
                                                            >
                                                        </label>
                                                        <label class="text-[10px] font-black uppercase tracking-wide text-slate-500">
                                                            Max
                                                            <input
                                                                class="admin-input !mt-1 h-10"
                                                                type="number"
                                                                min="1"
                                                                :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_ranges][${rowIndex}][maximum_quantity]`"
                                                                x-model="row.maximum_quantity"
                                                                @input="markFabricPriceMaximumManual(row)"
                                                                placeholder="∞"
                                                            >
                                                        </label>
                                                    </div>
                                                </td>
                                                <template x-for="(cell, cellIndex) in row.cells" :key="`fabric-cell-${gIndex}-${vIndex}-${rowIndex}-${cellIndex}`">
                                                    <td class="px-3 py-3 align-top">
                                                        <input
                                                            class="admin-input !mt-0 h-10"
                                                            :class="Number(value.fabric_price_table.highlight_column || 1) === cellIndex + 1 ? 'border-brand-red/50 bg-red-50/40 font-black text-brand-red' : ''"
                                                            :name="`fabric_price_tables[${gIndex}_${vIndex}][price_table_rows][${rowIndex}][${cellIndex + 1}]`"
                                                            x-model="row.cells[cellIndex]"
                                                            placeholder="$0.00"
                                                        >
                                                    </td>
                                                </template>
                                                <td class="px-3 py-3 align-top">
                                                    <button type="button" class="rounded-full px-2 py-1 text-lg font-black text-slate-400 hover:bg-red-50 hover:text-red-600" @click="removeFabricPriceRow(value.fabric_price_table, rowIndex)" x-show="value.fabric_price_table.rows.length > 1">×</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="btn btn-white text-xs" @click="addFabricPriceRow(value.fabric_price_table)">Add quantity tier</button>
                                <button type="button" class="btn btn-white text-xs" @click="addFabricPriceHeader(value.fabric_price_table)">Add price column</button>
                            </div>
                        </div>
                    </div>
                </template>
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
