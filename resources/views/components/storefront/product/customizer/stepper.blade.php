@props(['steps'])

<nav class="np-product-config-stepper" aria-label="Product customization steps">
    @foreach($steps as $step => $stepMeta)
        <button
            type="button"
            @click="openCustomizerStep({{ $step }})"
            :class="activeCustomizerStep === {{ $step }} ? 'is-active' : (activeCustomizerStep > {{ $step }} ? 'is-complete' : '')"
            :aria-current="activeCustomizerStep === {{ $step }} ? 'step' : null"
        >
            <span class="np-product-config-stepper__marker">
                <span x-show="activeCustomizerStep <= {{ $step }}">{{ $step }}</span>
                <svg x-show="activeCustomizerStep > {{ $step }}" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12 4 4 8-9"/></svg>
            </span>
            <strong>{{ $stepMeta['title'] }}</strong>
        </button>
    @endforeach
</nav>
