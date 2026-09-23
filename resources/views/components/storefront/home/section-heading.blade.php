@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'titleId' => null,
    'align' => 'center',
    'tone' => 'default',
])

<header {{ $attributes->class([
    'np-home-section-heading',
    'np-home-section-heading--left' => $align === 'left',
    'np-home-section-heading--inverse' => $tone === 'inverse',
]) }}>
    @if(filled($eyebrow))
        <span class="np-home-section-eyebrow">{{ $eyebrow }}</span>
    @endif

    <h2 @if($titleId) id="{{ $titleId }}" @endif class="np-home-section-title">{{ $title }}</h2>

    @if(filled($description))
        <p class="np-home-section-description">{{ $description }}</p>
    @endif
</header>
