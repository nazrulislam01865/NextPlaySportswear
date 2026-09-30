@props(['number', 'title', 'description' => null])

<header class="np-proto-step-header">
    <button
        type="button"
        class="np-proto-step-toggle"
        @click="openCustomizerStep({{ $number }})"
        :aria-expanded="activeCustomizerStep === {{ $number }} ? 'true' : 'false'"
        aria-controls="np-product-step-panel-{{ $number }}"
    >
        <span class="np-proto-step-number">{{ $number }}</span>
        <span class="np-proto-step-heading">
            <strong>{{ $title }}</strong>
            @if(filled($description))<small>{{ $description }}</small>@endif
        </span>
        <span class="np-proto-step-state is-complete" x-show="activeCustomizerStep > {{ $number }}" x-cloak>✓ Completed</span>
        <span class="np-proto-step-state is-active" x-show="activeCustomizerStep === {{ $number }}" x-cloak><i aria-hidden="true"></i> In Progress</span>
        <span class="np-proto-step-chevron" :class="activeCustomizerStep === {{ $number }} ? 'is-open' : ''" aria-hidden="true">⌄</span>
    </button>
    @isset($action)
        <div class="np-proto-step-action" x-show="activeCustomizerStep === {{ $number }}" x-cloak>{{ $action }}</div>
    @endisset
</header>
