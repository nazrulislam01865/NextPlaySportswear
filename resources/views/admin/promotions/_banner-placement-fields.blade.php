@php
    $placementState = $placementState ?? 'placements';
    $toggleMethod = $toggleMethod ?? 'togglePlacement';
    $fieldName = $fieldName ?? 'placements';
@endphp

<div class="np-banner-check-list">
    @foreach(\App\Support\PromotionBannerPlacement::labels() as $value => $label)
        <label>
            <input
                type="checkbox"
                name="{{ $fieldName }}[]"
                value="{{ $value }}"
                :checked="{{ $placementState }}.includes(@js($value))"
                @change="{{ $toggleMethod }}(@js($value), $event.target.checked)"
                @if(!empty($formId)) form="{{ $formId }}" @endif
            >
            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>
