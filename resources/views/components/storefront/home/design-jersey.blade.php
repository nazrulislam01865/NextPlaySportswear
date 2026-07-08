@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
    $image = data_get($section, 'image') ?: asset('storage/storefront/home/hero.webp');
@endphp

<section id="jersey" class="section-alt">
    <div class="container design">
        <div class="mockup">
            <img loading="lazy" decoding="async" src="{{ $image }}" alt="{{ $text('image_alt', 'Custom jersey mockup with team colors') }}" width="850" height="620">
        </div>
        <div class="text-block">
            <span class="small-red">{{ $text('eyebrow', 'Design your own') }}</span>
            <h2>{{ $text('title', 'Design Your Own Jersey') }}</h2>
            <p class="lead">{{ $text('description', 'Add your team logo, player names, numbers, colors, and style preferences.') }}</p>
            @if($items->isNotEmpty())
                <div class="steps">
                    @foreach($items as $item)
                        <div class="step"><span class="num">{{ $loop->iteration }}</span><div><h4>{{ data_get($item, 'title') }}</h4><p>{{ data_get($item, 'description') }}</p></div></div>
                    @endforeach
                </div>
            @endif
            <div class="hero-ctas">
                @if(filled($text('primary_label')))<a class="btn btn-red" href="{{ $text('primary_url', '#products') }}">{{ $text('primary_label', 'Start Your Order') }}</a>@endif
                @if(filled($text('secondary_label')))<a class="btn btn-light" href="{{ $text('secondary_url', '#bulk') }}">{{ $text('secondary_label', 'Request Bulk Quote') }}</a>@endif
            </div>
        </div>
    </div>
</section>
