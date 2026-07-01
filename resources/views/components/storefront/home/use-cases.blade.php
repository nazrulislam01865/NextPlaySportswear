@php
    $useCases = [
        ['image' => 'football.webp', 'alt' => 'Players on game day', 'title' => 'Game Day', 'description' => 'Uniforms, jerseys, shorts, and team sets.'],
        ['image' => 'training.webp', 'alt' => 'Athletes training', 'title' => 'Training', 'description' => 'T-shirts, practice wear, hoodies, and performance apparel.'],
        ['image' => 'bags.webp', 'alt' => 'Event and branded merchandise', 'title' => 'Events & Promotions', 'description' => 'Caps, bags, branded apparel, and giveaway items.'],
    ];
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">Built for use</span>
            <h2>Made for Play, Practice, Travel, and Team Events</h2>
            <p>Choose products based on how they will be used.</p>
        </div>
        <div class="use-grid">
            @foreach($useCases as $case)
                <article class="use-card">
                    <img loading="lazy" decoding="async" src="{{ asset('storage/storefront/home/'.$case['image']) }}" alt="{{ $case['alt'] }}" width="700" height="420">
                    <div><h3>{{ $case['title'] }}</h3><p>{{ $case['description'] }}</p></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
