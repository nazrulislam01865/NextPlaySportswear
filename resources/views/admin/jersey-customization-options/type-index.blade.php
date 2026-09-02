@php
    $typeLabel = $type->label();
    $isColor = $type->usesColorValue();
    $showsDescription = $type->usesDescription();
    $placeholder = $type->placeholder();
    $helpText = $type->helpText();
    $imageTitle = $type->imageTitle();
    $imageHint = $type->imageDescription();
    $imageCta = $type->imageCta();
    $selectedFabricImportIds = collect(old('source_option_ids', []))
        ->map(fn ($id) => (string) $id)
        ->values()
        ->all();
@endphp

<x-layouts.admin
    :title="$typeLabel"
    :eyebrow="'Master Data / '.$type->groupNumber().' '.$type->groupLabel()"
    :subtitle="$helpText"
    compact-header
>
    <div class="space-y-6">
        <x-admin.customization-type-tabs
            :heading="$type->groupNumber().' '.$type->groupLabel().' Submenus'"
            :aria-label="$type->groupLabel().' type navigation'"
            :links="$typeLinks"
            :active-type="$type"
            route-name="admin.jersey-customization-options.type"
            :size-option-link="\App\Support\ProductSizing::supportsMasterDataSizeOptions($type->group()) ? [
                'url' => route('admin.size-option-groups.index', ['customization' => $type->group()]),
                'number' => \App\Enums\JerseyCustomizationType::sizeOptionMenuNumberForGroup($type->group()),
                'label' => 'Size Options',
            ] : null"
        />

        @if(! empty($fabricImportSources))
            <div
                class="rounded-3xl border border-blue-100 bg-white p-4 shadow-card sm:p-5"
                x-data="{
                    importSources: @js($fabricImportSources),
                    targetLabel: @js($typeLabel),
                    sourceType: @js(old('source_type', '')),
                    selectedOptionIds: @js($selectedFabricImportIds),
                    selectedSource() {
                        return this.importSources.find((source) => source.value === this.sourceType) || null;
                    },
                    sourceOptions() {
                        const source = this.selectedSource();
                        return source ? source.options : [];
                    },
                    availableOptions() {
                        return this.sourceOptions().filter((option) => ! option.exists);
                    },
                    selectedCount() {
                        return this.selectedOptionIds.length;
                    },
                    selectAvailable() {
                        this.selectedOptionIds = this.availableOptions().map((option) => String(option.id));
                    },
                    clearSelected() {
                        this.selectedOptionIds = [];
                    },
                    sourceLabel() {
                        const source = this.selectedSource();
                        return source ? source.label : 'the selected source';
                    },
                    confirmImport() {
                        if (! this.sourceType) {
                            alert('Please choose a fabric source first.');
                            return false;
                        }

                        if (this.selectedCount() < 1) {
                            alert('Choose at least one fabric option to import.');
                            return false;
                        }

                        return confirm('Import ' + this.selectedCount() + ' selected fabric option(s) from ' + this.sourceLabel() + ' into ' + this.targetLabel + '? Existing duplicate slugs will be skipped.');
                    }
                }"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[.22em] text-brand-blue">Import Fabric Options</p>
                        <h3 class="mt-1 text-xl font-black text-brand-ink">Choose fabrics to import into {{ $typeLabel }}</h3>
                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">
                            Select the source list, then tick only the fabric options you want to copy. Imported rows are saved as separate {{ strtolower($typeLabel) }} options, so they can be edited without changing the source list.
                        </p>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('admin.jersey-customization-options.import-fabrics', $type->value) }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:min-w-[380px] lg:max-w-4xl"
                        @submit="if (! confirmImport()) $event.preventDefault();"
                    >
                        @csrf
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <select
                                class="admin-input mt-0 min-w-0 flex-1"
                                name="source_type"
                                x-model="sourceType"
                                @change="selectedOptionIds = []"
                                required
                            >
                                <option value="">Select fabric source</option>
                                @foreach($fabricImportSources as $source)
                                    <option value="{{ $source['value'] }}" @if($source['count'] < 1) disabled @endif>
                                        {{ $source['label'] }} — {{ $source['group_label'] }} ({{ $source['count'] }})
                                    </option>
                                @endforeach
                            </select>
                            <button
                                class="btn btn-navy shrink-0"
                                :disabled="selectedCount() < 1"
                            >
                                Import Selected
                            </button>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3" x-show="sourceType" x-cloak>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Available fabrics</p>
                                    <p class="mt-1 text-sm font-bold text-slate-700">
                                        <span x-text="selectedCount()"></span> selected
                                        <span class="text-slate-400">/</span>
                                        <span x-text="availableOptions().length"></span> importable
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-brand-blue hover:border-brand-blue"
                                        type="button"
                                        @click="selectAvailable()"
                                        :disabled="availableOptions().length < 1"
                                    >
                                        Select All
                                    </button>
                                    <button
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:border-slate-400"
                                        type="button"
                                        @click="clearSelected()"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                                <template x-if="sourceOptions().length < 1">
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-sm font-semibold text-slate-500">
                                        This fabric source has no options to import.
                                    </div>
                                </template>

                                <template x-for="option in sourceOptions()" :key="option.id">
                                    <label
                                        class="flex cursor-pointer gap-3 rounded-2xl border bg-white p-3 transition"
                                        :class="option.exists ? 'border-slate-200 opacity-70' : 'border-slate-200 hover:border-brand-blue'"
                                    >
                                        <input
                                            class="mt-1 h-4 w-4 rounded border-slate-300"
                                            type="checkbox"
                                            name="source_option_ids[]"
                                            :value="String(option.id)"
                                            x-model="selectedOptionIds"
                                            :disabled="option.exists"
                                        >
                                        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                            <template x-if="option.image_url">
                                                <img class="h-full w-full object-cover" :src="option.image_url" :alt="option.name">
                                            </template>
                                            <template x-if="! option.image_url">
                                                <span class="text-[10px] font-black text-slate-400">None</span>
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <strong class="text-sm text-brand-ink" x-text="option.name"></strong>
                                                <span
                                                    class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-amber-700"
                                                    x-show="option.exists"
                                                >
                                                    Already exists
                                                </span>
                                            </div>
                                            <p class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-slate-500" x-text="option.description || 'No details added.'"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <x-admin.customization-option-workspace
            :type-label="$typeLabel"
            :type-value="$type->value"
            :options="$options"
            :filters="$filters"
            :store-route="route('admin.jersey-customization-options.store')"
            edit-route-name="admin.jersey-customization-options.edit"
            destroy-route-name="admin.jersey-customization-options.destroy"
            :placeholder="$placeholder"
            :image-title="$imageTitle"
            :image-hint="$imageHint"
            :image-cta="$imageCta"
            :is-color="$isColor"
            :shows-description="$showsDescription"
        />
    </div>
</x-layouts.admin>
