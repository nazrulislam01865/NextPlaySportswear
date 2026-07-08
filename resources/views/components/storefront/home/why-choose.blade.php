@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Why NextPlay') }}</span>
            <h2>{{ $text('title', 'Why Teams Choose NextPlay Sportswear') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Practical support, clear ordering, and sportswear made around your needs.') }}</p>@endif
        </div>
        <div class="why-grid">
            @foreach($items as $item)
                <article class="why-card"><div class="mark">{{ data_get($item, 'icon', '✓') }}</div><h3>{{ data_get($item, 'title') }}</h3><p>{{ data_get($item, 'description') }}</p></article>
            @endforeach
        </div>
    </div>
</section>
