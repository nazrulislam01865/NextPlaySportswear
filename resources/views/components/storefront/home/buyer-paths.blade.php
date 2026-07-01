@props(['buyerPaths' => []])

@php
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
            <span class="small-red">Order by need</span>
            <h2>Shop by Who You’re Ordering For</h2>
            <p>Choose the path that fits your order.</p>
        </div>
        <div class="grid-4">
            @foreach($paths as $path)
                <article class="path-card">
                    <div class="path-icon" aria-hidden="true">{{ $path['icon'] }}</div>
                    <h3>{{ $path['title'] }}</h3>
                    <p>{{ $path['description'] }}</p>
                    <a class="link-red" href="{{ $path['url'] ?? '#bulk' }}">{{ $path['label'] ?? 'Start Your Order' }}</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
