@php
    $fieldValues = old('fields', $template->fields ?: [
        ['key' => 'custom_text', 'label' => 'Custom Text', 'type' => 'text', 'placeholder' => 'Enter your custom text', 'max_length' => 20, 'required' => true, 'enabled' => true],
        ['key' => 'custom_number', 'label' => 'Custom Number', 'type' => 'number', 'placeholder' => 'Enter number', 'max_length' => 4, 'required' => false, 'enabled' => true],
    ]);
    $editorFields = array_values($fieldValues);
    $formErrors = $errors->messages();
@endphp
<x-layouts.admin title="Configure Template Fields" compact-header>
    <x-admin.personalization-page heading="Configure Template Fields" eyebrow="Personalization Templates / Edit" :step="2" :template="$template">
        <x-slot:actions><a class="pt-button pt-outline" href="{{ route('admin.personalization-templates.edit', $template) }}">← Back</a><button form="pt-fields-form" type="submit" class="pt-button pt-primary">Preview & Save →</button></x-slot:actions>
        <div class="pt-summary-strip"><div><small>Template name</small><strong>{{ $template->name }}</strong></div><div><small>Last-level category</small><strong>{{ $template->category?->name }}</strong></div><span class="pt-pill {{ $template->is_active ? 'pt-pill-green' : 'pt-pill-gray' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></div>
        <div class="pt-columns pt-fields-columns" x-data="personalizationFieldEditor(@js($editorFields), @js($formErrors), @js($masterColors))">
            <form class="pt-card pt-fields-panel" id="pt-fields-form" method="POST" action="{{ route('admin.personalization-templates.fields.update', $template) }}" @submit="saveActive($event)" novalidate>
                @csrf
                @method('PUT')
                <div class="pt-panel-heading"><div><h3>Fields shown per item</h3><p class="pt-field-error" x-show="errors.fields?.length" x-cloak x-text="errors.fields?.[0]" role="alert"></p></div><button class="pt-button pt-primary" type="button" :disabled="fields.length >= 20" @click="addField()">＋ Add Field</button></div>
                <div class="pt-scroll"><table class="pt-table pt-fields-table"><thead><tr><th></th><th>Field name</th><th>Input type</th><th>Required</th><th>Max length</th><th>Action</th></tr></thead><tbody>
                <template x-for="(field,index) in fields" :key="field._uid"><tr :class="selected === index ? 'is-selected' : ''"><td><div class="pt-move"><button type="button" @click="move(index,-1)" :disabled="index===0" aria-label="Move field up">⌃</button><button type="button" @click="move(index,1)" :disabled="index===fields.length-1" aria-label="Move field down">⌄</button></div></td><td><strong x-text="field.label || 'New Field'"></strong><small x-text="field.placeholder"></small></td><td><span class="pt-field-chip" x-text="({text: 'Text', number: 'Number', color: 'Color', image: 'Image upload', file: 'File upload'})[field.type] || 'Text'"></span></td><td><span class="pt-field-chip" :class="field.required ? 'pt-required' : ''" x-text="field.required ? 'Required' : 'Optional'"></span></td><td><span class="pt-field-chip" x-text="['text','number'].includes(field.type) ? field.max_length + ' characters' : field.type === 'image' ? '5 MB / image' : field.type === 'file' ? '10 MB / file' : 'Color selection'"></span></td><td class="pt-actions-col"><button type="button" title="Edit field" @click="selected=index;editing=true">✎</button><button type="button" title="Remove field" @click="removeField(index)">×</button></td></tr></template>
                </tbody></table></div>
                <template x-if="fields.length && editing && fields[selected]"><div class="pt-edit-field">
                    <div class="pt-edit-heading"><h3 x-text="'Edit field: ' + (fields[selected]?.label || 'New field')"></h3><span>Selected field</span></div>
                    <div class="pt-form-two"><label class="pt-label">Field label <em>*</em><input class="pt-input" :class="{'is-invalid': fieldError(selected,'label')}" type="text" maxlength="120" x-model="fields[selected].label" @input="clearFieldError(selected,'label')" placeholder="Custom Text"><p class="pt-field-error" x-show="fieldError(selected,'label')" x-text="fieldError(selected,'label')" role="alert"></p></label><label class="pt-label">Field type<select class="pt-input" x-model="fields[selected].type" @change="clearFieldError(selected,'type')"><option value="text">Text</option><option value="number">Number</option><option value="color">Selectable colors</option><option value="image">Image upload</option><option value="file">File upload</option></select><p class="pt-field-error" x-show="fieldError(selected,'type')" x-text="fieldError(selected,'type')" role="alert"></p></label></div>
                    <div class="pt-form-two"><label class="pt-label" x-show="['text','number'].includes(fields[selected].type)">Input placeholder<input class="pt-input" type="text" maxlength="160" x-model="fields[selected].placeholder" @input="clearFieldError(selected,'placeholder')" placeholder="Enter your custom text"><p class="pt-field-error" x-show="fieldError(selected,'placeholder')" x-text="fieldError(selected,'placeholder')" role="alert"></p></label><label class="pt-label" x-show="['text','number'].includes(fields[selected].type)">Maximum characters<input class="pt-input" :class="{'is-invalid': fieldError(selected,'max_length')}" type="number" min="1" max="250" x-model.number="fields[selected].max_length" @input="clearFieldError(selected,'max_length')"><p class="pt-field-error" x-show="fieldError(selected,'max_length')" x-text="fieldError(selected,'max_length')" role="alert"></p></label></div>
                    <div class="pt-colors-editor" x-show="fields[selected].type === 'color'" x-cloak>
                        <strong>Available colors from Master Data</strong>
                        <p class="pt-color-help">Select the colors customers can choose for this field.</p>
                        <div class="pt-color-options">
                            <template x-for="color in masterColors" :key="color.hex">
                                <label class="pt-color-choice">
                                    <input type="checkbox" :checked="selectedColors(selected).includes(color.hex)" @change="toggleColor(selected, color.hex, $event.target.checked)">
                                    <span class="pt-color-dot" :style="`background:${color.hex}`"></span>
                                    <span x-text="color.name"></span>
                                </label>
                            </template>
                        </div>
                        <p class="pt-color-help" x-show="!masterColors.length">No colors are configured for this product family in Master Data.</p>
                        <p class="pt-field-error" x-show="fieldError(selected,'color_options')" x-text="fieldError(selected,'color_options')" role="alert"></p>
                    </div>
                    <div class="pt-field-switches"><label><input type="checkbox" x-model="fields[selected].required"> Required</label><label><input type="checkbox" x-model="fields[selected].enabled"> Enabled</label></div>
                    <div class="pt-edit-footer"><button type="button" class="pt-button pt-primary" @click="editing=false">Apply Field Changes</button></div>
                </div></template>
                <template x-for="(field,index) in fields" :key="'hidden-'+field._uid">
                    <div><input type="hidden" :name="`fields[${index}][key]`" :value="field.key"><input type="hidden" :name="`fields[${index}][label]`" :value="field.label"><input type="hidden" :name="`fields[${index}][type]`" :value="field.type"><input type="hidden" :name="`fields[${index}][placeholder]`" :value="field.placeholder"><input type="hidden" :name="`fields[${index}][max_length]`" :value="field.max_length"><input type="hidden" :name="`fields[${index}][required]`" :value="field.required ? 1 : 0"><input type="hidden" :name="`fields[${index}][enabled]`" :value="field.enabled ? 1 : 0"><input type="hidden" :name="`fields[${index}][color_options]`" :value="JSON.stringify(selectedColors(index))"></div>
                </template>
                <div x-show="fields.length===0" class="pt-empty">No fields yet. Select Add Field to create the first input.</div>
            </form>
            <aside class="pt-side"><div class="pt-card pt-aside-panel"><h3>Live field preview</h3><div class="pt-preview-mini"><div class="pt-section-banner"><span class="pt-round">3</span><div><strong>{{ $template->section_title }}</strong><p>{{ $template->description }}</p></div></div><div class="pt-mini-body"><template x-for="field in fields.filter(f=>f.enabled)" :key="field._uid"><label class="pt-label"><span x-text="field.label"></span><em x-show="field.required">*</em><template x-if="field.type === 'color'"><div class="pt-color-options"><template x-for="color in field.color_options || []" :key="color.hex || color"><span class="pt-color-dot" :style="`background:${color.hex || color}`" :title="color.name || color.hex || color"></span></template></div></template><template x-if="field.type === 'image' || field.type === 'file'"><input class="pt-input" type="file" :accept="field.type === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp'"></template><template x-if="field.type === 'text' || field.type === 'number'"><input class="pt-input" type="text" :placeholder="field.placeholder" :maxlength="field.max_length" readonly></template><small class="pt-help" x-text="['text','number'].includes(field.type) ? `Maximum ${field.max_length} characters` : field.type === 'color' ? 'Available color choices' : 'Upload one file per item'"></small></label></template><p x-show="!fields.some(f=>f.enabled)" class="pt-help">Enable a field to see its preview.</p></div></div></div></aside>
        </div>
    </x-admin.personalization-page>
    <script>
    function personalizationFieldEditor(initial, initialErrors = {}, masterColors = []) {
        let uid = 0;
        return {
            fields: (initial || []).map(f => ({...f, _uid: ++uid, required: !!Number(f.required), enabled: !!Number(f.enabled)})),
            masterColors,
            selectedColors(index) { return (this.fields[index]?.color_options || []).map(color => String(color.hex || color).toUpperCase()); },
            toggleColor(index, hex, checked) {
                const choices = new Set(this.selectedColors(index));
                checked ? choices.add(hex) : choices.delete(hex);
                this.fields[index].color_options = [...choices];
                this.clearFieldError(index,'color_options');
            },
            errors: { ...initialErrors },
            init() {
                const invalid = Object.keys(this.errors).find(key => /^fields\.\d+\./.test(key));
                if (invalid) this.selected = Number(invalid.split('.')[1]);
            },
            selected: 0,
            editing: true,
            fieldError(i, property) { return this.errors[`fields.${i}.${property}`]?.[0] || ''; },
            clearFieldError(i, property) { delete this.errors[`fields.${i}.${property}`]; delete this.errors.fields; },
            addField() {
                if (this.fields.length >= 20) return;
                this.fields.push({_uid: ++uid, key: '', label: '', type:'text', placeholder:'', max_length:60, color_options:[], required:false, enabled:true});
                this.selected=this.fields.length-1; this.editing=true; this.errors = {};
                this.$nextTick(()=>document.querySelector('.pt-edit-field input[type=text]')?.focus());
            },
            removeField(i) { this.fields.splice(i,1); this.selected = this.fields.length ? Math.min(i,this.fields.length-1) : 0; this.errors = {}; },
            move(i,shift) { const next=i+shift;if(next<0||next>=this.fields.length)return; const [f]=this.fields.splice(i,1);this.fields.splice(next,0,f);this.selected=next; this.editing=true; this.errors = {}; },
            saveActive(event) {
                const errors = {};
                if (!this.fields.length) errors.fields = ['Add at least one field.'];
                if (!this.fields.some(field => field.enabled)) errors.fields = ['Enable at least one field.'];
                const keys = new Set();
                this.fields.forEach((field, i) => {
                    const label = String(field.label || '').trim();
                    if (!label) errors[`fields.${i}.label`] = ['Enter a field label.'];
                    else if (label.length > 120) errors[`fields.${i}.label`] = ['Maximum 120 characters.'];
                    if (!['text','number','color','image','file'].includes(field.type)) errors[`fields.${i}.type`] = ['Select an input type.'];
                    if (field.type === 'color' && field.enabled && !this.selectedColors(i).length) errors[`fields.${i}.color_options`] = ['Select at least one available color.'];
                    if (['text','number'].includes(field.type) && (Number(field.max_length) < 1 || Number(field.max_length) > 250 || !Number.isInteger(Number(field.max_length)))) errors[`fields.${i}.max_length`] = ['Enter a number between 1 and 250.'];
                    if (String(field.placeholder || '').length > 160) errors[`fields.${i}.placeholder`] = ['Maximum 160 characters.'];
                    if (!field.key) field.key = label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'') || `field_${i+1}`;
                    if (keys.has(field.key)) errors[`fields.${i}.label`] = ['Use a different field label; the generated key already exists.'];
                    keys.add(field.key);
                });
                this.errors = errors;
                const first = Object.keys(errors)[0];
                if (first) {
                    event.preventDefault();
                    const index = Number(first.split('.')[1]);
                    if (Number.isInteger(index) && !Number.isNaN(index)) { this.selected=index; this.editing=true; }
                    this.$nextTick(() => {
                        const target = Number.isInteger(index) && !Number.isNaN(index)
                            ? document.querySelector('.pt-edit-field input.is-invalid, .pt-edit-field input[type=text]')
                            : document.querySelector('.pt-panel-heading .pt-field-error');
                        target?.scrollIntoView({behavior:'smooth',block:'center'});
                        if (target?.matches('input')) target.focus();
                    });
                }
            },
        };
    }
    </script>
</x-layouts.admin>
