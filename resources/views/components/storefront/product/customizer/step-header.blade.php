@props(['number', 'title', 'description' => null])

<header class="np-proto-step-header{{ isset($action) ? ' np-proto-step-header--has-action' : '' }}">
    <button
        type="button"
        class="np-proto-step-toggle"
        @click="toggleCustomizerStep({{ $number }})"
        :aria-expanded="isCustomizerStepOpen({{ $number }}) ? 'true' : 'false'"
        aria-controls="np-product-step-panel-{{ $number }}"
    >
        <span class="np-proto-step-number">{{ $number }}</span>
        <span class="np-proto-step-heading">
            <strong>{{ $title }}</strong>
            @if(filled($description))<small>{{ $description }}</small>@endif
        </span>
        <span class="np-proto-step-state is-complete" x-show="completedCustomizerStep >= {{ $number }} && activeCustomizerStep !== {{ $number }}" x-cloak>✓ Completed</span>
        <span class="np-proto-step-state is-active" x-show="activeCustomizerStep === {{ $number }}" x-cloak><i aria-hidden="true"></i> In Progress</span>
    </button>
    @isset($action)
        <div class="np-proto-step-action" x-show="isCustomizerStepOpen({{ $number }})" x-cloak>{{ $action }}</div>
    @endisset
    <button
        type="button"
        class="np-proto-step-chevron-button"
        @click="toggleCustomizerStep({{ $number }})"
        :aria-expanded="isCustomizerStepOpen({{ $number }}) ? 'true' : 'false'"
        aria-controls="np-product-step-panel-{{ $number }}"
        aria-label="Toggle {{ $title }} section"
    >
        <svg class="np-proto-step-chevron" :class="isCustomizerStepOpen({{ $number }}) ? 'is-open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
</header>
