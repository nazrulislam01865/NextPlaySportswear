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
        <div class="pt-columns pt-fields-columns" x-data="personalizationFieldEditor(@js($editorFields), @js($formErrors), @js(route('admin.personalization-templates.pantones.search', $template)), @js($existingPantones))">
            <form class="pt-card pt-fields-panel" id="pt-fields-form" method="POST" action="{{ route('admin.personalization-templates.fields.update', $template) }}" @submit="saveActive($event)" novalidate>
                @csrf
                @method('PUT')
                <div class="pt-panel-heading"><div><h3>Fields shown per item</h3><p class="pt-field-error" x-show="errors.fields?.length" x-cloak x-text="errors.fields?.[0]" role="alert"></p></div><button class="pt-button pt-primary" type="button" :disabled="fields.length >= 20" @click="addField()">＋ Add Field</button></div>
                <div class="pt-scroll"><table class="pt-table pt-fields-table"><thead><tr><th></th><th>Field name</th><th>Input type</th><th>Required</th><th>Max length</th><th>Action</th></tr></thead><tbody>
                <template x-for="(field,index) in fields" :key="field._uid"><tr :class="selected === index ? 'is-selected' : ''"><td><div class="pt-move"><button type="button" @click="move(index,-1)" :disabled="index===0" aria-label="Move field up">⌃</button><button type="button" @click="move(index,1)" :disabled="index===fields.length-1" aria-label="Move field down">⌄</button></div></td><td><strong x-text="field.label || 'New Field'"></strong><small x-text="field.placeholder"></small></td><td><span class="pt-field-chip" x-text="({text: 'Text', number: 'Number', color: 'Color', image: 'Image upload', file: 'File upload'})[field.type] || 'Text'"></span></td><td><span class="pt-field-chip" :class="field.required ? 'pt-required' : ''" x-text="field.required ? 'Required' : 'Optional'"></span></td><td><span class="pt-field-chip" x-text="['text','number'].includes(field.type) ? field.max_length + ' characters' : field.type === 'image' ? '5 MB / image' : field.type === 'file' ? '10 MB / file' : 'Color selection'"></span></td><td class="pt-actions-col"><button type="button" title="Edit field" @click="selected=index;editing=true;closePantones()">✎</button><button type="button" title="Remove field" @click="removeField(index)">×</button></td></tr></template>
                </tbody></table></div>
                <template x-if="fields.length && editing && fields[selected]"><div class="pt-edit-field">
                    <div class="pt-edit-heading"><h3 x-text="'Edit field: ' + (fields[selected]?.label || 'New field')"></h3><span>Selected field</span></div>
                    <div class="pt-form-two"><label class="pt-label">Field label <em>*</em><input class="pt-input" :class="{'is-invalid': fieldError(selected,'label')}" type="text" maxlength="120" x-model="fields[selected].label" @input="clearFieldError(selected,'label')" placeholder="Custom Text"><p class="pt-field-error" x-show="fieldError(selected,'label')" x-text="fieldError(selected,'label')" role="alert"></p></label><label class="pt-label">Field type<select class="pt-input" x-model="fields[selected].type" @change="clearFieldError(selected,'type'); closePantones()"><option value="text">Text</option><option value="number">Number</option><option value="color">Selectable colors</option><option value="image">Image upload</option><option value="file">File upload</option></select><p class="pt-field-error" x-show="fieldError(selected,'type')" x-text="fieldError(selected,'type')" role="alert"></p></label></div>
                    <div class="pt-form-two"><label class="pt-label" x-show="['text','number'].includes(fields[selected].type)">Input placeholder<input class="pt-input" type="text" maxlength="160" x-model="fields[selected].placeholder" @input="clearFieldError(selected,'placeholder')" placeholder="Enter your custom text"><p class="pt-field-error" x-show="fieldError(selected,'placeholder')" x-text="fieldError(selected,'placeholder')" role="alert"></p></label><label class="pt-label" x-show="['text','number'].includes(fields[selected].type)">Maximum characters<input class="pt-input" :class="{'is-invalid': fieldError(selected,'max_length')}" type="number" min="1" max="250" x-model.number="fields[selected].max_length" @input="clearFieldError(selected,'max_length')"><p class="pt-field-error" x-show="fieldError(selected,'max_length')" x-text="fieldError(selected,'max_length')" role="alert"></p></label></div>
                    <div class="pt-colors-editor" x-show="fields[selected].type === 'color'" x-cloak @click.outside="closePantones()" @keydown.escape.stop="closePantones()">
                        <strong>Available Pantone colors from Master Data</strong>
                        <p class="pt-color-help">Search by Pantone name, shade, code or HEX. Select the colors customers can choose.</p>
                        <div class="pt-pantone-search-wrap">
                            <div class="pt-pantone-search" :class="{'is-invalid':fieldError(selected,'color_options')}">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                                <input type="search" x-model="pantoneQuery" @input="handlePantoneInput($event.target.value)" @input.debounce.250ms="loadPantones(true)" maxlength="100" autocomplete="off" placeholder="Search Pantone name, code, shade or HEX" aria-label="Search Pantone colors" role="combobox" aria-autocomplete="list" aria-controls="pt-pantone-results" :aria-expanded="searchOpen.toString()">
                                <button x-show="pantoneQuery" type="button" class="pt-pantone-clear" @click="handlePantoneInput(''); $nextTick(()=>$el.previousElementSibling.focus())" aria-label="Clear Pantone search">×</button>
                            </div>
                            <div class="pt-pantone-dropdown" x-show="searchOpen" x-cloak id="pt-pantone-results">
                                <div class="pt-pantone-results" x-show="pantoneResults.length > 0" role="listbox" aria-label="Pantone colors" aria-multiselectable="true">
                                    <template x-for="color in pantoneResults" :key="color.id">
                                        <button type="button" class="pt-pantone-result" role="option" :aria-selected="isPantoneSelected(selected,color.id).toString()" @click="togglePantone(selected,color)">
                                            <span class="pt-color-dot" :style="`background:${color.hex}`"></span>
                                            <span class="pt-pantone-result-name"><strong x-text="color.name"></strong><small x-text="[color.code, color.hex].filter(Boolean).join(' · ')"></small></span>
                                            <span class="pt-pantone-check" x-text="isPantoneSelected(selected,color.id) ? '✓' : '+'" aria-hidden="true"></span>
                                        </button>
                                    </template>
                                </div>
                                <button type="button" class="pt-pantone-more" x-show="pantoneHasMore && !pantoneLoading" @click="loadPantones(false)">Show more colors</button>
                                <p class="pt-pantone-message" x-show="pantonePage > 0 && !pantoneLoading && !pantoneError && pantoneResults.length === 0" x-cloak role="status">No matching color found</p>
                            </div>
                        </div>
                        <p class="pt-field-error" x-show="pantoneError" x-cloak x-text="pantoneError" role="alert"></p>
                        <div class="pt-pantone-selected" x-show="selectedColors(selected).length">
                            <template x-for="color in selectedColors(selected)" :key="color.id || color.hex + color.name">
                                <span class="pt-pantone-chip">
                                    <span class="pt-color-dot" :style="`background:${color.hex}`"></span>
                                    <span><strong x-text="color.name"></strong><small x-text="[color.code, color.hex].filter(Boolean).join(' · ')"></small><small x-show="!color.id" class="pt-pantone-unavailable">Unavailable in active Pantone master</small></span>
                                    <button type="button" @click="removePantone(selected,color)" :aria-label="'Remove ' + color.name">×</button>
                                </span>
                            </template>
                        </div>
                        <p class="pt-field-error" x-show="fieldError(selected,'color_options')" x-text="fieldError(selected,'color_options')" role="alert"></p>
                    </div>
                    <div class="pt-field-switches"><label><input type="checkbox" x-model="fields[selected].required"> Required</label><label><input type="checkbox" x-model="fields[selected].enabled"> Enabled</label></div>
                    <div class="pt-edit-footer"><button type="button" class="pt-button pt-primary" @click="editing=false">Apply Field Changes</button></div>
                </div></template>
                <template x-for="(field,index) in fields" :key="'hidden-'+field._uid">
                    <div><input type="hidden" :name="`fields[${index}][key]`" :value="field.key"><input type="hidden" :name="`fields[${index}][label]`" :value="field.label"><input type="hidden" :name="`fields[${index}][type]`" :value="field.type"><input type="hidden" :name="`fields[${index}][placeholder]`" :value="field.placeholder"><input type="hidden" :name="`fields[${index}][max_length]`" :value="field.max_length"><input type="hidden" :name="`fields[${index}][required]`" :value="field.required ? 1 : 0"><input type="hidden" :name="`fields[${index}][enabled]`" :value="field.enabled ? 1 : 0"><input type="hidden" :name="`fields[${index}][color_options]`" :value="JSON.stringify(selectedColors(index).map(c=>c.id))"></div>
                </template>
                <div x-show="fields.length===0" class="pt-empty">No fields yet. Select Add Field to create the first input.</div>
            </form>
            <aside class="pt-side"><div class="pt-card pt-aside-panel"><h3>Live field preview</h3><div class="pt-preview-mini"><div class="pt-section-banner"><span class="pt-round">3</span><div><strong>{{ $template->section_title }}</strong><p>{{ $template->description }}</p></div></div><div class="pt-mini-body"><template x-for="field in fields.filter(f=>f.enabled)" :key="field._uid"><label class="pt-label"><span x-text="field.label"></span><em x-show="field.required">*</em><template x-if="field.type === 'color'"><div class="pt-color-options"><template x-for="color in field.color_options || []" :key="color.id || color.hex || color"><span class="pt-color-dot" :style="`background:${color.hex || color}`" :title="color.name || color.hex || color"></span></template></div></template><template x-if="field.type === 'image' || field.type === 'file'"><input class="pt-input" type="file" :accept="field.type === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp'"></template><template x-if="field.type === 'text' || field.type === 'number'"><input class="pt-input" type="text" :placeholder="field.placeholder" :maxlength="field.max_length" readonly></template><small class="pt-help" x-text="['text','number'].includes(field.type) ? `Maximum ${field.max_length} characters` : field.type === 'color' ? 'Available color choices' : 'Upload one file per item'"></small></label></template><p x-show="!fields.some(f=>f.enabled)" class="pt-help">Enable a field to see its preview.</p></div></div></div></aside>
        </div>
    </x-admin.personalization-page>
    <script>
    function personalizationFieldEditor(initial, initialErrors = {}, pantoneUrl = '', existingPantones = []) {
        let uid = 0;
        const byId = new Map(existingPantones.map(color => [Number(color.id), color]));
        const byHex = new Map();
        existingPantones.forEach(color => {
            const hex = String(color.hex).toUpperCase();
            if (!byHex.has(hex)) byHex.set(hex, []);
            byHex.get(hex).push(color);
        });
        function restoreColors(saved) {
            if (typeof saved === 'string') { try { saved = JSON.parse(saved); } catch (_) { saved = []; } }
            if (!Array.isArray(saved)) return [];
            return saved.map(item => {
                const original = (item && typeof item === 'object') ? item : {};
                const id = Number(original.id || (typeof item === 'number' || /^\d+$/.test(String(item)) ? item : 0));
                const hex = String(original.hex || (typeof item === 'string' && item.startsWith('#') ? item : '')).toUpperCase();
                const named = (byHex.get(hex) || []).find(color => color.name === original.name);
                const matched = id ? byId.get(id) : (named || (byHex.get(hex) || [])[0]);
                return matched || {id:null, name:original.name || hex || 'Unknown Pantone', code:original.code || '', hex};
            }).filter(color => color.hex && /^#[0-9A-F]{6}$/.test(color.hex));
        }
        return {
            fields: (initial || []).map(f => ({...f, color_options:restoreColors(f.color_options), _uid: ++uid, required: !!Number(f.required), enabled: !!Number(f.enabled)})),
            pantoneUrl,
            pantoneQuery:'', searchOpen:false, pantoneResults:[], pantonePage:0,
            pantoneHasMore:false, pantoneLoading:false, pantoneError:'', pantoneRequest:0,
            selectedColors(index) { return this.fields[index]?.color_options || []; },
            isPantoneSelected(index,id) { return this.selectedColors(index).some(color => Number(color.id) === Number(id)); },
            togglePantone(index,color) {
                const choices = [...this.selectedColors(index)];
                const found = choices.findIndex(selected => Number(selected.id) === Number(color.id));
                if (found >= 0) choices.splice(found,1);
                else if (choices.length < 100) choices.push({...color});
                else { this.errors[`fields.${index}.color_options`] = ['You can select up to 100 Pantone colors per field.']; return; }
                this.fields[index].color_options = choices;
                this.clearFieldError(index,'color_options');
            },
            removePantone(index,color) {
                this.fields[index].color_options = this.selectedColors(index).filter(item => item !== color);
                this.clearFieldError(index,'color_options');
            },
            // Do not display or fetch a color list just because the field gained focus.
            // Clear stale results immediately on every edit, before the debounced request.
            handlePantoneInput(value) {
                this.pantoneQuery = String(value ?? '');
                this.pantoneRequest++;
                this.searchOpen = false;
                this.pantoneResults = [];
                this.pantonePage = 0;
                this.pantoneHasMore = false;
                this.pantoneLoading = false;
                this.pantoneError = '';
            },
            closePantones() {
                this.pantoneRequest++;
                this.searchOpen = false;
                this.pantoneLoading = false;
            },
            async loadPantones(reset=true) {
                const term = this.pantoneQuery.trim();
                if (term.length < 3) {
                    this.handlePantoneInput(this.pantoneQuery);
                    return;
                }
                if (!reset && (!this.searchOpen || !this.pantoneHasMore || this.pantoneLoading)) return;
                const id = ++this.pantoneRequest;
                const nextPage = reset ? 1 : this.pantonePage + 1;
                if (reset) {
                    this.searchOpen = false;
                    this.pantoneResults = [];
                    this.pantonePage = 0;
                    this.pantoneHasMore = false;
                }
                this.pantoneLoading = true;
                this.pantoneError = '';
                try {
                    const url = new URL(this.pantoneUrl, window.location.origin);
                    url.searchParams.set('q', term);
                    url.searchParams.set('page', nextPage);
                    const response = await fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'});
                    if (!response.ok) throw new Error('Unable to retrieve Pantone colors. Please try again.');
                    const data = await response.json();
                    if (id !== this.pantoneRequest || this.pantoneQuery.trim() !== term) return;
                    const matches = Array.isArray(data.colors) ? data.colors : [];
                    this.pantoneResults = reset ? matches : [...this.pantoneResults, ...matches];
                    this.pantoneHasMore = !!data.has_more;
                    this.pantonePage = nextPage;
                    // Show matching colors or the no-results message after a completed 3+ character search.
                    this.searchOpen = true;
                } catch (error) {
                    if (id === this.pantoneRequest && this.pantoneQuery.trim() === term) {
                        this.searchOpen = false;
                        this.pantoneError = error.message || 'Unable to retrieve Pantone colors.';
                    }
                } finally {
                    if (id === this.pantoneRequest) this.pantoneLoading = false;
                }
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
                this.selected=this.fields.length-1; this.editing=true; this.closePantones(); this.errors = {};
                this.$nextTick(()=>document.querySelector('.pt-edit-field input[type=text]')?.focus());
            },
            removeField(i) { this.fields.splice(i,1); this.selected = this.fields.length ? Math.min(i,this.fields.length-1) : 0; this.closePantones(); this.errors = {}; },
            move(i,shift) { const next=i+shift;if(next<0||next>=this.fields.length)return; const [f]=this.fields.splice(i,1);this.fields.splice(next,0,f);this.selected=next; this.editing=true; this.closePantones(); this.errors = {}; },
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
                    if (field.type === 'color' && field.enabled && !this.selectedColors(i).length) errors[`fields.${i}.color_options`] = ['Select at least one active Pantone color.'];
                    else if (field.type === 'color' && this.selectedColors(i).some(color => !color.id)) errors[`fields.${i}.color_options`] = ['Remove unavailable selections and choose active Pantone colors.'];
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
