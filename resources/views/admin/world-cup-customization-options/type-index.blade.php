@php
    $typeLabel = $type->label();
    $showsDescription = $type->usesDescription();
    $placeholder = $type->placeholder();
    $helpText = $type->helpText();
    $imageTitle = $type->imageTitle();
    $imageHint = $type->imageDescription();
    $imageCta = $type->imageCta();
@endphp

<x-layouts.admin
    :title="$typeLabel"
    :eyebrow="'Master Data / 1.24 World Cup Customization / '.$type->categoryNumber().' '.$type->categoryLabel()"
    :subtitle="$helpText"
    compact-header
>
    <div class="space-y-6">
        <x-admin.customization-type-tabs
            :heading="$type->categoryNumber().' '.$type->categoryLabel().' Customization Submenus'"
            :aria-label="$type->categoryLabel().' customization navigation'"
            :links="$typeLinks"
            :active-type="$type"
            route-name="admin.world-cup-customization-options.type"
        />

        <x-admin.customization-option-workspace
            :type-label="$typeLabel"
            :type-value="$type->value"
            :options="$options"
            :filters="$filters"
            :store-route="route('admin.world-cup-customization-options.store')"
            edit-route-name="admin.world-cup-customization-options.edit"
            destroy-route-name="admin.world-cup-customization-options.destroy"
            :placeholder="$placeholder"
            :image-title="$imageTitle"
            :image-hint="$imageHint"
            :image-cta="$imageCta"
            :shows-description="$showsDescription"
        />
    </div>
</x-layouts.admin>
