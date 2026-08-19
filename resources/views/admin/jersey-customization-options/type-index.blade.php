@php
    $typeLabel = $type->label();
    $isColor = $type->usesColorValue();
    $showsDescription = $type->usesDescription();
    $columnCount = 4 + ($isColor ? 1 : 0) + ($showsDescription ? 1 : 0);
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
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-4 sm:p-5">
                <p class="text-xs font-black uppercase tracking-[.22em] text-slate-400">
                    {{ $type->groupNumber() }} {{ $type->groupLabel() }} Submenus
                </p>
            </div>
            <div class="flex gap-2 overflow-x-auto p-4 sm:p-5" aria-label="{{ $type->groupLabel() }} type navigation">
                @foreach($typeLinks as $link)
                    <a
                        href="{{ route('admin.jersey-customization-options.type', $link['type']->value) }}"
                        @class([
                            'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-2xl border px-4 text-sm font-black transition',
                            'border-brand-blue bg-brand-blue text-white shadow-sm' => $type === $link['type'],
                            'border-slate-200 bg-white text-slate-600 hover:border-brand-blue hover:text-brand-blue' => $type !== $link['type'],
                        ])
                    >
                        <span>{{ $link['number'] }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach

                @if(\App\Support\ProductSizing::supportsMasterDataSizeOptions($type->group()))
                    <a
                        href="{{ route('admin.size-option-groups.index', ['customization' => $type->group()]) }}"
                        class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-600 transition hover:border-brand-blue hover:text-brand-blue"
                    >
                        <span>{{ \App\Enums\JerseyCustomizationType::sizeOptionMenuNumberForGroup($type->group()) }}</span>
                        <span>Size Options</span>
                    </a>
                @endif
            </div>
        </div>

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

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.22em] text-brand-blue">Already Added</p>
                        <h2 class="mt-1 text-2xl font-black text-brand-ink">{{ $typeLabel }} List</h2>
                    </div>
                    <form method="GET" class="flex w-full gap-2 sm:w-auto">
                        <input
                            class="admin-input mt-0 min-w-0 sm:w-72"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Search {{ strtolower($typeLabel) }}"
                        >
                        <button class="btn btn-white shrink-0">Search</button>
                    </form>
                </div>

                <div class="admin-table-scroll" tabindex="0" aria-label="{{ $typeLabel }} table">
                    <table class="admin-table min-w-[760px] text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Name</th>
                                @if($isColor)
                                    <th class="px-5 py-4">Color Value</th>
                                @endif
                                @if($showsDescription)
                                    <th class="px-5 py-4">Details</th>
                                @endif
                                <th class="px-5 py-4">Image</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($options as $item)
                                <tr>
                                    <td class="px-5 py-4">
                                        <strong class="text-brand-ink">{{ $item->name }}</strong>
                                    </td>
                                    @if($isColor)
                                        <td class="px-5 py-4">
                                            @if($item->color_hex)
                                                <div class="flex items-center gap-2 font-bold text-slate-700">
                                                    <span
                                                        class="h-6 w-6 rounded-full border border-slate-300 shadow-sm"
                                                        style="background: {{ $item->color_hex }}"
                                                    ></span>
                                                    <span>{{ $item->color_hex }}</span>
                                                </div>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($showsDescription)
                                        <td class="max-w-[280px] px-5 py-4">
                                            @if(filled($item->description))
                                                <p class="max-h-12 overflow-hidden text-sm font-semibold leading-6 text-slate-600">{{ $item->description }}</p>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                                @if($item->primaryImage?->publicUrl())
                                                    <img
                                                        class="h-full w-full object-cover"
                                                        src="{{ $item->primaryImage->publicUrl() }}"
                                                        alt="{{ $item->primaryImage->name }}"
                                                    >
                                                @else
                                                    <span class="text-[10px] font-black text-slate-400">None</span>
                                                @endif
                                            </div>
                                            @if($item->images_count > 1)
                                                <span class="text-xs font-semibold text-slate-500">+{{ $item->images_count - 1 }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-black',
                                            'bg-emerald-50 text-emerald-700' => $item->is_active,
                                            'bg-slate-100 text-slate-500' => ! $item->is_active,
                                        ])>
                                            {{ $item->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="admin-row-actions">
                                            <a
                                                class="admin-row-action border-slate-200"
                                                href="{{ route('admin.jersey-customization-options.edit', $item) }}?return_type={{ $type->value }}"
                                            >
                                                Edit
                                            </a>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.jersey-customization-options.destroy', $item) }}"
                                                onsubmit="return confirm('Delete this {{ strtolower($typeLabel) }} option?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $columnCount }}" class="px-5 py-14 text-center">
                                        <p class="font-black text-brand-ink">No {{ strtolower($typeLabel) }} option has been added.</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-500">Use the form beside this table to add the first item.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-5">{{ $options->links('pagination.nextplay') }}</div>
            </section>

            <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6 xl:sticky xl:top-36 xl:self-start">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Add New Item</p>
                <h2 class="mt-1 text-2xl font-black text-brand-ink">Add {{ $typeLabel }}</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Only the necessary fields are shown for this option type.</p>

                <form
                    class="mt-6 space-y-5"
                    method="POST"
                    action="{{ route('admin.jersey-customization-options.store') }}"
                    enctype="multipart/form-data"
                    x-data="{
                        optionName: @js(old('name', '')),
                        colorValue: @js(old('color_hex', '#111827')),
                        imageFileName: '',
                        imagePreview: '',
                        handleImageSelection(event) {
                            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                            this.imageFileName = file ? file.name : '';
                            if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                            this.imagePreview = file ? URL.createObjectURL(file) : '';
                        }
                    }"
                >
                    @csrf
                    <input type="hidden" name="_return_to_type" value="1">
                    <input type="hidden" name="type" value="{{ $type->value }}">

                    <label class="admin-label">
                        {{ $isColor ? 'Color name' : $typeLabel.' name' }}
                        <input
                            class="admin-input"
                            name="name"
                            x-model="optionName"
                            value="{{ old('name') }}"
                            maxlength="160"
                            placeholder="{{ $placeholder }}"
                            required
                        >
                    </label>

                    @if($isColor)
                        <label class="admin-label">
                            Color value
                            <div class="mt-2 flex items-center gap-3">
                                <input
                                    class="h-12 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 bg-white p-1"
                                    type="color"
                                    x-model="colorValue"
                                    @input="colorValue = $event.target.value.toUpperCase()"
                                >
                                <input
                                    class="admin-input mt-0"
                                    name="color_hex"
                                    x-model="colorValue"
                                    maxlength="7"
                                    placeholder="#111827"
                                    required
                                >
                            </div>
                        </label>
                    @endif

                    @if($showsDescription)
                        <label class="admin-label">
                            Details
                            <textarea
                                class="admin-textarea min-h-28 resize-y"
                                name="description"
                                maxlength="2000"
                                placeholder="Example: Lightweight dry-fit mesh with breathable texture and quick-dry finish."
                            >{{ old('description') }}</textarea>
                            <span class="mt-1 block text-xs font-semibold text-slate-500">
                                Add only useful fabric information. This helps the admin and customer understand the material quickly.
                            </span>
                        </label>
                    @endif

                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-lg shadow-sm">🖼</span>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-brand-ink">{{ $imageTitle }}</p>
                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $imageHint }}</p>
                            </div>
                        </div>

                        <label class="mt-4 block cursor-pointer rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-brand-blue hover:bg-blue-50/30">
                            <input
                                class="sr-only"
                                type="file"
                                name="images[0][image_file]"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                                @change="handleImageSelection"
                            >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="grid h-20 w-full shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100 sm:w-24">
                                    <template x-if="imagePreview">
                                        <img class="h-full w-full object-cover" :src="imagePreview" alt="Selected option image preview">
                                    </template>
                                    <template x-if="! imagePreview">
                                        <span class="text-2xl text-slate-400">+</span>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="inline-flex min-h-10 items-center rounded-xl bg-brand-blue px-4 text-sm font-black text-white">{{ $imageCta }}</span>
                                    <p class="mt-2 truncate text-xs font-bold text-slate-500" x-text="imageFileName || 'JPG, PNG, WEBP or AVIF up to 5MB'"></p>
                                </div>
                            </div>
                        </label>

                        <input type="hidden" name="images[0][existing_id]" value="">
                        <input type="hidden" name="images[0][name]" value="">
                        <input type="hidden" name="images[0][image_url]" value="">
                        <input type="hidden" name="images[0][is_primary]" value="1">
                        <input type="hidden" name="images[0][sort_order]" value="0">
                    </div>

                    <button class="btn btn-red w-full">+ Add {{ $typeLabel }}</button>
                </form>
            </aside>
        </div>
    </div>
</x-layouts.admin>
