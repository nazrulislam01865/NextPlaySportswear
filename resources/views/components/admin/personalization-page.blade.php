@once
<link rel="stylesheet" href="{{ asset('assets/personalization-color-options-20261027.css') }}?v=1">
@endonce
@props(['heading', 'eyebrow', 'description' => null, 'step' => null, 'template' => null])
<div class="pt-page">
    <div class="pt-page-head">
        <div>
            <p class="pt-eyebrow">{{ $eyebrow }}</p>
            <h2>{{ $heading }}</h2>
            @if($description)<p class="pt-muted">{{ $description }}</p>@endif
        </div>
        <div class="pt-head-actions">{{ $actions ?? '' }}</div>
    </div>
    @if($step)
        <x-admin.personalization-wizard :step="$step" :template="$template" />
    @endif
    {{ $slot }}
</div>
