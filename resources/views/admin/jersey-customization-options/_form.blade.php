@php
    use App\Enums\JerseyCustomizationType;

    $isEdit = $option->exists;
    $selectedType = old('type', $option->type instanceof JerseyCustomizationType ? $option->type->value : $option->type);
    $selectedTypeEnum = JerseyCustomizationType::tryFrom((string) $selectedType) ?: JerseyCustomizationType::NeckAndCollar;
    $imageTitle = match ($selectedTypeEnum) {
        JerseyCustomizationType::Color => 'Color swatch image',
        JerseyCustomizationType::NeckAndCollar => 'Neck/collar reference image',
        JerseyCustomizationType::Fabric => 'Fabric texture image',
        JerseyCustomizationType::SleevesAndCuffs => 'Sleeve/cuff reference image',
        JerseyCustomizationType::JerseyStyle => 'Jersey style preview image',
    };
    $imageDescription = match ($selectedTypeEnum) {
        JerseyCustomizationType::Color => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
        JerseyCustomizationType::NeckAndCollar => 'Optional. Add a clear close-up of the neckline or collar shape.',
        JerseyCustomizationType::Fabric => 'Optional. Add a texture or material close-up for this fabric.',
        JerseyCustomizationType::SleevesAndCuffs => 'Optional. Add a close-up of the sleeve or cuff finish.',
        JerseyCustomizationType::JerseyStyle => 'Optional. Add a full jersey preview for this style.',
    };
    $existingImages = $option->relationLoaded('images') ? $option->images->keyBy('id') : collect();
    $returnType = request('return_type');
    $cancelUrl = $returnType
        ? route('admin.jersey-customization-options.type', $returnType)
        : route('admin.jersey-customization-options.index');
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

<div class="space-y-6" x-data="{ type: @js($selectedType) }">
    @if($returnType)
        <input type="hidden" name="_return_to_type" value="1">
    @endif
    <x-admin.section-card
        title="Jersey Customization Option"
        description="Create a reusable jersey customization value. Images are optional."
    >
        <div class="space-y-5">
            <label class="admin-label">
                Name
                <input
                    class="admin-input"
                    name="name"
                    value="{{ old('name', $option->name) }}"
                    maxlength="160"
                    placeholder="Example: V-Neck"
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
                x-show="type === @js(JerseyCustomizationType::Color->value)"
                x-cloak
            >
                Value
                <div class="mt-2 flex items-center gap-3">
                    <input
                        class="h-11 w-14 shrink-0 cursor-pointer rounded-xl border border-slate-300 bg-white p-1"
                        type="color"
                        value="{{ old('color_hex', $option->color_hex ?: '#111827') }}"
                        @input="$refs.colorHex.value = $event.target.value.toUpperCase()"
                    >
                    <input
                        x-ref="colorHex"
                        class="admin-input mt-0"
                        name="color_hex"
                        value="{{ old('color_hex', $option->color_hex) }}"
                        maxlength="7"
                        placeholder="#111827"
                        :required="type === @js(JerseyCustomizationType::Color->value)"
                    >
                </div>
                <span class="mt-1 block text-xs font-medium text-slate-500">
                    This value is shown only for Color options.
                </span>
            </label>

            <label
                class="admin-label"
                x-show="type === @js(JerseyCustomizationType::Fabric->value)"
                x-cloak
            >
                Fabric details
                <textarea
                    class="admin-textarea min-h-28 resize-y"
                    name="description"
                    maxlength="2000"
                    placeholder="Example: Lightweight dry-fit mesh with breathable texture and quick-dry finish."
                >{{ old('description', $option->description) }}</textarea>
                <span class="mt-1 block text-xs font-medium text-slate-500">
                    Details are shown only for Fabric options.
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
