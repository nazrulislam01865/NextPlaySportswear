@props([
    'groupKey',
    'group',
    'isCustomizationActive' => false,
    'isSizeOptionActive' => false,
    'activeCustomizationType' => null,
    'activeCustomizationGroup' => null,
    'activeSizeCustomizationGroup' => null,
])

@php
    $isCurrentCustomizationGroup = ($isCustomizationActive && $activeCustomizationGroup === $groupKey)
        || ($isSizeOptionActive && $activeSizeCustomizationGroup === $groupKey);
@endphp

<x-admin.sidebar-customization-group
    :number="$group['number']"
    :label="$group['label']"
    :active="$isCurrentCustomizationGroup"
>
    <x-admin.sidebar-customization-type-links
        :types="$group['types']"
        route-name="admin.jersey-customization-options.type"
        :is-active="$isCustomizationActive"
        :active-type="$activeCustomizationType"
    />

    @if(\App\Support\ProductSizing::supportsMasterDataSizeOptions($groupKey))
        @php($sizeOptionMenuNumber = \App\Enums\JerseyCustomizationType::sizeOptionMenuNumberForGroup($groupKey))
        @php($isCurrentSizeOptionLink = $isSizeOptionActive && $activeSizeCustomizationGroup === $groupKey)
        <a
            href="{{ route('admin.size-option-groups.index', ['customization' => $groupKey]) }}"
            aria-label="Size Options"
            data-sidebar-tooltip="Size Options"
            @if($isCurrentSizeOptionLink) data-sidebar-active="true" @endif
            @class([
                'flex min-h-9 min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-bold transition',
                'bg-brand-red text-white' => $isCurrentSizeOptionLink,
                'text-slate-400 hover:bg-white/10 hover:text-white' => ! $isCurrentSizeOptionLink,
            ])
        >
            <span class="shrink-0 text-[10px] opacity-80">{{ $sizeOptionMenuNumber }}</span>
            <span class="min-w-0 truncate" data-sidebar-label>Size Options</span>
        </a>
    @endif
</x-admin.sidebar-customization-group>
