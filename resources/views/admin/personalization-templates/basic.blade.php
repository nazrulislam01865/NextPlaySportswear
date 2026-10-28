@php
    $editing = $template->exists;
    $target = $editing ? route('admin.personalization-templates.update', $template) : route('admin.personalization-templates.store');
    $categoryId = old('category_id', $template->category_id);
    $categoryOptions = $categories->map(fn ($category) => [
        'value' => (string) $category->id,
        'label' => $category->display_path,
        'tag' => $editing && (int) $category->id === (int) $template->category_id ? 'Personalization Added' : null,
    ])->all();
    $formErrors = $errors->messages();
@endphp
<x-layouts.admin title="{{ $editing ? 'Edit' : 'Add' }} Personalization Template" compact-header>
    <x-admin.personalization-page heading="{{ $editing ? 'Edit' : 'Add' }} Personalization Template" eyebrow="Personalization Templates / {{ $editing ? 'Edit' : 'Create' }}" :step="1" :template="$template">
        <x-slot:actions><a class="pt-button pt-outline" href="{{ route('admin.personalization-templates.index') }}">← Back</a><button class="pt-button pt-primary" type="submit" form="pt-basic" name="action" value="continue">Save & Continue →</button></x-slot:actions>
        <div class="pt-columns pt-basic-columns"><form id="pt-basic" method="POST" action="{{ $target }}" class="pt-card pt-main-panel" x-data="personalizationBasicForm(@js($formErrors))" @submit="validate($event)" @pt-select-change="clear($event.detail.name)" novalidate>
            @csrf
            @if($editing)
                @method('PUT')
            @endif
            <h3>Basic Information</h3>
            <label class="pt-label" for="pt-category">Product category <em>*</em></label>
            <div :class="{'pt-has-error': errors.category_id?.length}">
                <x-admin.personalization-select name="category_id" id="pt-category" :selected="$categoryId" :options="$categoryOptions" placeholder="Select a product category" search-placeholder="Search categories..." />
                @if(! $editing && $categories->isEmpty())
                    <p class="pt-muted">All available last-level categories already have personalization setups. Edit an existing setup from Personalization Categories.</p>
                @endif
                <p class="pt-field-error" x-show="errors.category_id?.length" x-cloak x-text="errors.category_id?.[0]" role="alert"></p>
            </div>
            <div class="pt-form-two">
                <div><label class="pt-label" for="pt-name">Template name <em>*</em></label><input class="pt-input" :class="{'is-invalid': errors.name?.length}" id="pt-name" name="name" maxlength="160" value="{{ old('name', $template->name) }}" placeholder="Custom Text & Number (Caps)" @input="clear('name')" :aria-invalid="!!errors.name?.length"><p class="pt-field-error" x-show="errors.name?.length" x-cloak x-text="errors.name?.[0]" role="alert"></p></div>
                <div><label class="pt-label" for="pt-section">Customer section title <em>*</em></label><input class="pt-input" :class="{'is-invalid': errors.section_title?.length}" id="pt-section" name="section_title" maxlength="180" value="{{ old('section_title', $template->section_title) }}" placeholder="Custom Text & Number" @input="clear('section_title')" :aria-invalid="!!errors.section_title?.length"><p class="pt-field-error" x-show="errors.section_title?.length" x-cloak x-text="errors.section_title?.[0]" role="alert"></p></div>
            </div>
            <label class="pt-label" for="pt-description">Description below section heading</label><textarea class="pt-input pt-textarea" :class="{'is-invalid': errors.description?.length}" id="pt-description" name="description" maxlength="2000" rows="3" @input="clear('description')">{{ old('description', $template->description) }}</textarea><p class="pt-field-error" x-show="errors.description?.length" x-cloak x-text="errors.description?.[0]" role="alert"></p>
            <label class="pt-label" for="pt-shared">Shared-entry checkbox label</label><input class="pt-input" :class="{'is-invalid': errors.shared_entry_label?.length}" id="pt-shared" name="shared_entry_label" maxlength="180" value="{{ old('shared_entry_label', $template->shared_entry_label) }}" @input="clear('shared_entry_label')"><p class="pt-field-error" x-show="errors.shared_entry_label?.length" x-cloak x-text="errors.shared_entry_label?.[0]" role="alert"></p>
            <div class="pt-form-bottom">
                <div><label class="pt-label" for="pt-share-enabled">Allow customers to use one entry for all pieces</label><label class="pt-switch-line"><input type="hidden" name="shared_entry_enabled" value="0"><input id="pt-share-enabled" type="checkbox" name="shared_entry_enabled" value="1" @checked(old('shared_entry_enabled', $template->shared_entry_enabled)) @change="clear('shared_entry_enabled')"><span class="pt-toggle-track" aria-hidden="true"></span><span>Enabled</span></label><p class="pt-field-error" x-show="errors.shared_entry_enabled?.length" x-cloak x-text="errors.shared_entry_enabled?.[0]" role="alert"></p></div>
                <div><label class="pt-label">Template status</label><div class="pt-radio-line"><label><input type="radio" name="is_active" value="1" @checked((string)old('is_active', (int)$template->is_active) === '1') @change="clear('is_active')"><span>Active</span></label><label><input type="radio" name="is_active" value="0" @checked((string)old('is_active', (int)$template->is_active) === '0') @change="clear('is_active')"><span>Inactive</span></label></div><p class="pt-field-error" x-show="errors.is_active?.length" x-cloak x-text="errors.is_active?.[0]" role="alert"></p></div>
            </div>
            <div class="pt-form-actions pt-form-actions--spaced"><button type="submit" name="action" value="draft" class="pt-button pt-outline">{{ $editing ? 'Save Changes' : 'Save Draft' }}</button><button type="submit" name="action" value="continue" class="pt-button pt-primary">Save &amp; Continue →</button></div>
        </form>
        <aside class="pt-side"><div class="pt-card pt-aside-panel"><h3>Customer section preview</h3><div class="pt-preview-mini"><div class="pt-section-banner"><span class="pt-round">3</span><div><strong id="pt-live-title">{{ old('section_title', $template->section_title) }}</strong><p id="pt-live-description">{{ old('description', $template->description) }}</p></div></div><div class="pt-mini-body"><label class="pt-shared-box"><input type="checkbox" disabled><strong id="pt-live-shared">{{ old('shared_entry_label', $template->shared_entry_label) }}</strong></label></div></div></div>
</aside></div>
    </x-admin.personalization-page>
    <script>document.addEventListener('DOMContentLoaded',()=>{for(const [from,to] of [['pt-section','pt-live-title'],['pt-description','pt-live-description'],['pt-shared','pt-live-shared']]){const input=document.getElementById(from),output=document.getElementById(to);input?.addEventListener('input',()=>{output.textContent=input.value;});}});</script>
</x-layouts.admin>
