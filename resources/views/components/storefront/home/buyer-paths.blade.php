@props(['buyerPaths' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $paths = count($buyerPaths) ? $buyerPaths : [
        ['icon' => '♜', 'title' => 'Teams & Leagues', 'description' => 'Uniforms and gear for full teams, clubs, and local leagues.', 'url' => '#bulk', 'label' => 'Start Your Order'],
        ['icon' => '★', 'title' => 'Schools & Colleges', 'description' => 'Custom jerseys, PE uniforms, event apparel, and spirit wear.', 'url' => '#bulk', 'label' => 'Start Your Order'],
        ['icon' => '▣', 'title' => 'Businesses & Events', 'description' => 'Branded apparel, caps, bags, and giveaway items.', 'url' => '#bulk', 'label' => 'Request Bulk Quote'],
        ['icon' => '✓', 'title' => 'Individual Buyers', 'description' => 'Shop selected products online and customize where available.', 'url' => '#products', 'label' => 'Shop Now'],
    ];
@endphp

<section class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Order by need') }}</span>
            <h2>{{ $text('title', 'Shop by Who You’re Ordering For') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Choose the path that fits your order.') }}</p>@endif
        </div>
        <div class="grid-4">
            @foreach($paths as $path)
                <article class="path-card">
                    <div class="path-icon" aria-hidden="true">{{ $path['icon'] ?? '•' }}</div>
                    <h3>{{ $path['title'] }}</h3>
                    <p>{{ $path['description'] }}</p>
                    <a class="link-red" href="{{ $path['url'] ?? '#bulk' }}">{{ $path['label'] ?? 'Start Your Order' }}</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
