@php
    use App\Enums\WorldCupCustomizationType;

    $isEdit = $option->exists;
    $selectedType = old('type', $option->type instanceof WorldCupCustomizationType ? $option->type->value : $option->type);
    $selectedTypeEnum = WorldCupCustomizationType::tryFrom((string) $selectedType) ?: WorldCupCustomizationType::DrawstringMaterialsOption;
    $imageTitle = $selectedTypeEnum->imageTitle();
    $imageDescription = $selectedTypeEnum->imageDescription();
    $descriptionTypes = collect(WorldCupCustomizationType::cases())
        ->filter(fn (WorldCupCustomizationType $type) => $type->usesDescription())
        ->map(fn (WorldCupCustomizationType $type) => $type->value)
        ->values()
        ->all();
    $existingImages = $option->relationLoaded('images') ? $option->images->keyBy('id') : collect();
    $returnType = request('return_type');
    $cancelUrl = $returnType
        ? route('admin.world-cup-customization-options.type', $returnType)
        : route('admin.world-cup-customization-options.index');
    $submittedImages = old('images');

    $initialImages = $submittedImages !== null
        ? collect($submittedImages)->map(function ($image, $index) use ($existingImages) {
            $existing = $existingImages->get((int) ($image['existing_id'] ?? 0));

            return [
                'key' => filled($image['existing_id'] ?? null) ? 'existing-'.$image['existing_id'] : 'old-'.$index,
                'existing_id' => $image['existing_id'] ?? '',
                'name' => $image['name'] ?? '',
                'image_url' => $image['image_url'] ?? '',
                'preview' => $existing?->publicUrl(),
                'is_primary' => filter_var($image['is_primary'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        })->values()->all()
        : $existingImages->values()->map(fn ($image) => [
            'key' => 'existing-'.$image->id,
            'existing_id' => $image->id,
            'name' => $image->name,
            'image_url' => $image->image_url,
            'preview' => $image->publicUrl(),
            'is_primary' => $image->is_primary,
        ])->all();
@endphp

<div
    class="space-y-6"
    x-data="{
        type: @js($selectedType),
        descriptionTypes: @js($descriptionTypes)
    }"
>
    @if($returnType)
        <input type="hidden" name="_return_to_type" value="1">
    @endif

    <x-admin.section-card
        title="Customization Option"
        description="Create a reusable product customization value. Images are optional."
    >
        <div class="space-y-5">
            <label class="admin-label">
                Name
                <input
                    class="admin-input"
                    name="name"
                    value="{{ old('name', $option->name) }}"
                    maxlength="160"
                    placeholder="{{ $selectedTypeEnum->placeholder() }}"
                    required
                >
            </label>

            <label class="admin-label">
                Type
                <select class="admin-input" name="type" x-model="type" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label
                class="admin-label"
                x-show="descriptionTypes.includes(type)"
                x-cloak
            >
                Details
                <textarea
                    class="admin-textarea min-h-28 resize-y"
                    name="description"
                    maxlength="2000"
                    placeholder="Example: Lightweight dry-fit mesh with breathable texture and quick-dry finish."
                >{{ old('description', $option->description) }}</textarea>
                <span class="mt-1 block text-xs font-medium text-slate-500">
                    Add only useful material information that helps explain the option.
                </span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card
        :title="$imageTitle"
        :description="$imageDescription"
    >
        <x-admin.image-collection-field
            name="images"
            :images="$initialImages"
            title="Images"
            description="Upload an image or provide an image link. Select one image as primary."
            compact
        />
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:justify-end">
        <a class="btn btn-white" href="{{ $cancelUrl }}">Cancel</a>
        <button class="btn btn-red">{{ $isEdit ? 'Update Option' : 'Create Option' }}</button>
    </div>
</div>
