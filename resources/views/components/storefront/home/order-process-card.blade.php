@props(['step' => [], 'number' => 1, 'last' => false])

@php
    $image = filled(data_get($step, 'image')) ? (string) data_get($step, 'image') : null;
    // Bundled prototype photographs are the defaults; uploaded admin media always wins.
    $stepId = (string) data_get($step, 'id', '');
    $defaultIndex = preg_match('/^process-item-([1-5])$/', $stepId, $matches)
        ? (int) $matches[1]
        : (int) $number;
    // Some older saved steps still reference the generic catalog placeholder.
    // Prefer the prototype photograph unless an actual admin image was uploaded.
    $usesGenericPlaceholder = blank(data_get($step, 'image_path'))
        && parse_url((string) $image, PHP_URL_PATH) === '/images/product-placeholder.svg';
    if (($image === null || $usesGenericPlaceholder) && $defaultIndex >= 1 && $defaultIndex <= 5) {
        $image = asset(sprintf('images/ordering-process/step-%02d.webp', $defaultIndex));
    }
@endphp

<article class="np-ordering-card" aria-label="Step {{ $number }}: {{ data_get($step, 'title') }}">
    <div class="np-ordering-card__photo">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ data_get($step, 'image_alt') ?: data_get($step, 'title') }}"
                 loading="lazy" decoding="async" width="282" height="262">
        @endif
    </div>
    <div class="np-ordering-card__body">
        <span class="np-ordering-card__step">STEP {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
        <h3 class="np-ordering-card__title">{{ data_get($step, 'title') }}</h3>
        <p class="np-ordering-card__description">{{ data_get($step, 'description') }}</p>
    </div>
    @unless($last)
        <span class="np-ordering-card__arrow" aria-hidden="true">→</span>
    @endunless
</article>
