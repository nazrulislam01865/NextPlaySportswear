@props(['product'])

<div class="grid gap-3">
    <div class="np-option-tiles">
        <article>
            <span class="np-option-tiles__icon teal">♙</span>
            <div>
                <strong>Roster Fields</strong>
                <small>Collect per-item details such as names, numbers, initials, labels, or identifiers.</small>
            </div>
            <button
                type="button"
                class="np-secondary-button"
                @click="rosterPanelOpen = !rosterPanelOpen"
                x-text="rosterPanelOpen ? 'Hide roster fields' : 'Show roster fields'"
            ></button>
        </article>
    </div>

    <details
        class="np-config-panel"
        :open="rosterPanelOpen"
        @toggle="rosterPanelOpen = $el.open"
    >
        <summary>Roster fields</summary>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="font-black">Per-item roster details</h3>
                <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">
                    Use this for any product that needs customer-entered details for each ordered item. The editor remains available for every product profile.
                </p>
            </div>

            <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold">
                <input type="hidden" name="jersey_roster_enabled" :value="rosterEnabled ? 1 : 0">
                <input type="checkbox" x-model="rosterEnabled">
                Show roster step to customers
            </label>
        </div>

        <div x-show="rosterEnabled" x-cloak class="mt-4 rounded-2xl border border-slate-200 p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="admin-label">
                    Customer heading
                    <input
                        class="admin-input"
                        name="jersey_roster_title"
                        value="{{ old('jersey_roster_title', $product->jersey_roster_title ?: \App\Support\ProductRoster::DEFAULT_TITLE) }}"
                    >
                </label>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                    <input type="hidden" name="jersey_roster_optional" :value="rosterOptional ? 1 : 0">
                    <input type="checkbox" x-model="rosterOptional">
                    <span>
                        <strong class="block text-sm">Customer may skip roster details</strong>
                        <small class="text-xs text-slate-500">When disabled, every enabled roster field marked required must be completed.</small>
                    </span>
                </label>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-black">Fields shown for each item</h4>
                    <p class="text-xs text-slate-500">
                        Rows follow selected size quantities when sizes are used; otherwise they follow the product order quantity.
                    </p>
                </div>
                <button type="button" class="np-secondary-button" @click="addRosterField()">＋ Add Field</button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(field,index) in rosterFields" :key="index">
                    <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-[1.5fr_150px_130px_auto] lg:items-end">
                        <input type="hidden" :name="`jersey_roster_fields[${index}][key]`" x-model="field.key">
                        <input type="hidden" :name="`jersey_roster_fields[${index}][enabled]`" value="1">

                        <label class="admin-label">
                            Customer label
                            <input
                                class="admin-input"
                                :name="`jersey_roster_fields[${index}][label]`"
                                x-model="field.label"
                                @blur="if(!field.key) field.key = field.label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'')"
                                placeholder="Name"
                            >
                        </label>

                        <label class="admin-label">
                            Input type
                            <select class="admin-input" :name="`jersey_roster_fields[${index}][type]`" x-model="field.type">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                            </select>
                        </label>

                        <label class="admin-label">
                            Max length
                            <input
                                class="admin-input"
                                type="number"
                                min="1"
                                max="120"
                                :name="`jersey_roster_fields[${index}][max_length]`"
                                x-model="field.max_length"
                            >
                        </label>

                        <div class="flex flex-wrap gap-3 pb-3">
                            <input type="hidden" :name="`jersey_roster_fields[${index}][required]`" :value="field.required ? 1 : 0">
                            <label class="text-xs font-bold"><input type="checkbox" x-model="field.required"> Required</label>
                            <button type="button" class="np-danger-link" @click="rosterFields.splice(index,1)">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="!rosterEnabled" x-cloak class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm font-semibold text-slate-500">
            Roster fields are currently hidden from customers. Enable “Show roster step to customers” to customize the heading and fields.
        </div>
    </details>
</div>
