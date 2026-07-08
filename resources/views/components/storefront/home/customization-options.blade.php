@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Your details') }}</span>
            <h2>{{ $text('title', 'Make It Yours') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Choose the details that make your team or brand stand out.') }}</p>@endif
        </div>
        <div class="option-grid">
            @foreach($items as $item)
                <div class="option"><div class="dot">{{ data_get($item, 'icon', '•') }}</div><strong>{{ data_get($item, 'title') }}</strong></div>
            @endforeach
        </div>
        @if(filled($text('primary_label')))
            <p class="home-center-action"><a class="btn btn-light" href="{{ $text('primary_url', '#jersey') }}">{{ $text('primary_label', 'Customize Yours') }}</a></p>
        @endif
    </div>
</section>
