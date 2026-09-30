@props([
    'backStep' => null,
    'backLabel' => 'Back',
    'nextStep' => null,
    'nextLabel' => 'Next',
    'submit' => false,
    'backToProduct' => false,
])

<footer class="np-proto-step-navigation">
    @if($backToProduct)
        <button type="button" class="btn btn-outline np-proto-back-button" @click="document.querySelector('.np-product-detail-hero')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
            <span aria-hidden="true">←</span> {{ $backLabel }}
        </button>
    @elseif($backStep !== null)
        <button type="button" class="btn btn-outline np-proto-back-button" @click="openCustomizerStep({{ (int) $backStep }})">
            <span aria-hidden="true">←</span> {{ $backLabel }}
        </button>
    @else
        <span></span>
    @endif

    @if($submit)
        <button type="submit" class="btn btn-secondary np-proto-next-button">{{ $nextLabel }}</button>
    @elseif($nextStep !== null)
        <button type="button" class="btn btn-secondary np-proto-next-button" @click="openCustomizerStep({{ (int) $nextStep }})">
            {{ $nextLabel }} <span aria-hidden="true">→</span>
        </button>
    @endif
</footer>
