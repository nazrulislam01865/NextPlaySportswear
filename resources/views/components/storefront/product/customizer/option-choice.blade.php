@props(['group', 'value', 'multiple' => false])

@php
    $groupId = $group['id'];
    $valueId = $value['id'];
    $images = collect($value['images'] ?? [])
        ->map(fn ($image) => is_array($image) ? ($image['url'] ?? null) : $image)
        ->filter()
        ->values();
    $preview = $images->first() ?: ($value['image'] ?? null);
    $color = $value['color'] ?? null;
@endphp

<button
    type="button"
    class="np-proto-option-card"
    @if($multiple)
        @click="toggle(@js($group), @js($valueId))"
        :class="(multiSelections[@js($groupId)] || []).includes(@js($valueId)) ? 'is-selected' : ''"
        :aria-checked="(multiSelections[@js($groupId)] || []).includes(@js($valueId)) ? 'true' : 'false'"
        role="checkbox"
    @else
        @click="choose(@js($group), @js($valueId))"
        :class="selections[@js($groupId)] === @js($valueId) ? 'is-selected' : ''"
        :aria-checked="selections[@js($groupId)] === @js($valueId) ? 'true' : 'false'"
        role="radio"
    @endif
>
    <span class="np-proto-option-media" aria-hidden="true">
        @if($preview)
            <img src="{{ $preview }}" alt="" loading="lazy" decoding="async">
        @elseif(filled($color))
            <span class="np-proto-option-swatch" style="background-color: {{ $color }}"></span>
        @else
            <span class="np-proto-option-placeholder"></span>
        @endif
    </span>

    <span class="np-proto-option-copy">
        <strong>{{ $value['label'] }}</strong>
        @if(filled($value['description'] ?? null))
            <small>{{ $value['description'] }}</small>
        @endif
        <small class="np-proto-option-charge" x-text="chargeLabel(@js($value))"></small>
    </span>

    <span class="np-proto-selected-check" aria-hidden="true">✓</span>
</button>
