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
        class="np-config-panel np-roster-config-panel"
        :open="rosterPanelOpen"
        @toggle="rosterPanelOpen = $el.open"
    >
        <summary>Roster fields</summary>

        <div class="np-roster-editor">
            <div class="np-roster-editor__header">
                <div class="np-roster-editor__intro">
                    <h3>Per-item roster details</h3>
                    <p>
                        Use this when customers need to enter details for each ordered item, such as a player name or number.
                    </p>
                </div>

                <label class="np-roster-toggle-card">
                    <input type="hidden" name="jersey_roster_enabled" :value="rosterEnabled ? 1 : 0">
                    <input type="checkbox" x-model="rosterEnabled">
                    <span>
                        <strong>Show roster step to customers</strong>
                        <small>Turn this off to remove the complete roster step from the storefront.</small>
                    </span>
                </label>
            </div>

            <div x-show="rosterEnabled" x-cloak class="np-roster-editor__body">
                <div class="np-roster-settings-grid">
                    <label class="admin-label np-roster-heading-field">
                        Customer heading
                        <input
                            class="admin-input"
                            name="jersey_roster_title"
                            value="{{ old('jersey_roster_title', $product->jersey_roster_title ?: \App\Support\ProductRoster::DEFAULT_TITLE) }}"
                        >
                    </label>

                    <label class="np-roster-toggle-card np-roster-toggle-card--secondary">
                        <input type="hidden" name="jersey_roster_optional" :value="rosterOptional ? 1 : 0">
                        <input type="checkbox" x-model="rosterOptional">
                        <span>
                            <strong>Customer may skip roster details</strong>
                            <small>Turn this off when required roster fields must be completed.</small>
                        </span>
                    </label>
                </div>

                <div class="np-roster-fields-toolbar">
                    <div>
                        <h4>Fields shown for each item</h4>
                        <p>Rows follow selected size quantities when sizes are used; otherwise they follow the product order quantity.</p>
                    </div>
                    <button type="button" class="np-secondary-button" @click="addRosterField()">＋ Add Field</button>
                </div>

                <div class="np-roster-fields-list">
                    <template x-for="(field,index) in rosterFields" :key="index">
                        <div class="np-roster-field-row">
                            <input type="hidden" :name="`jersey_roster_fields[${index}][key]`" x-model="field.key">
                            <input type="hidden" :name="`jersey_roster_fields[${index}][enabled]`" value="1">

                            <label class="admin-label np-roster-control np-roster-control--label">
                                Customer label
                                <input
                                    class="admin-input"
                                    :name="`jersey_roster_fields[${index}][label]`"
                                    x-model="field.label"
                                    @blur="if(!field.key) field.key = field.label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'')"
                                    placeholder="Name"
                                >
                            </label>

                            <label class="admin-label np-roster-control np-roster-control--type">
                                Input type
                                <select class="admin-input" :name="`jersey_roster_fields[${index}][type]`" x-model="field.type">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                </select>
                            </label>

                            <label class="admin-label np-roster-control np-roster-control--length">
                                Max length
                                <input
                                    class="admin-input"
                                    type="number"
                                    min="1"
                                    :name="`jersey_roster_fields[${index}][max_length]`"
                                    x-model="field.max_length"
                                >
                            </label>

                            <div class="np-roster-control np-roster-control--required">
                                <span class="np-roster-control__label">Required</span>
                                <input type="hidden" :name="`jersey_roster_fields[${index}][required]`" :value="field.required ? 1 : 0">
                                <label class="np-roster-checkbox-box">
                                    <input type="checkbox" x-model="field.required">
                                    <span x-text="field.required ? 'Yes' : 'No'"></span>
                                </label>
                            </div>

                            <div class="np-roster-control np-roster-control--action">
                                <span class="np-roster-control__label">Action</span>
                                <button type="button" class="np-roster-remove-button" @click="rosterFields.splice(index,1)">Remove</button>
                            </div>
                        </div>
                    </template>

                    <div x-show="rosterFields.length === 0" x-cloak class="np-roster-empty-state">
                        <strong>No roster fields added.</strong>
                        <span>Add a field to collect per-item information from customers.</span>
                        <button type="button" class="np-secondary-button" @click="addRosterField()">＋ Add Field</button>
                    </div>
                </div>
            </div>

            <div x-show="!rosterEnabled" x-cloak class="np-roster-disabled-state">
                <strong>Roster step is hidden from customers.</strong>
                <span>Enable “Show roster step to customers” when this product needs per-item roster details.</span>
            </div>
        </div>
    </details>
</div>
