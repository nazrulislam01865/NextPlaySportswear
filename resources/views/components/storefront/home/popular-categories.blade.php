@php
    $popularCategories = [
        ['image' => 'football.webp', 'alt' => 'Football jerseys', 'title' => 'Football Jerseys', 'description' => 'Personalized football jerseys for players and fans.', 'href' => '#products', 'label' => 'Customize Yours'],
        ['image' => 'baseball.webp', 'alt' => 'Baseball uniforms', 'title' => 'Baseball Uniforms', 'description' => 'Custom baseball uniforms for teams and tournaments.', 'href' => '#products', 'label' => 'Customize Yours'],
        ['image' => 'basketball.webp', 'alt' => 'Basketball jerseys', 'title' => 'Basketball Jerseys', 'description' => 'Clean jersey styles with logo, name, and number options.', 'href' => '#products', 'label' => 'Customize Yours'],
        ['image' => 'soccer.webp', 'alt' => 'Soccer kits', 'title' => 'Soccer Kits', 'description' => 'Custom soccer kits for clubs, schools, and fan groups.', 'href' => '#products', 'label' => 'Customize Yours'],
        ['image' => 'hoodies.webp', 'alt' => 'Hoodies and sweatshirts', 'title' => 'Hoodies & Sweatshirts', 'description' => 'Team hoodies for sideline, travel, and spirit wear.', 'href' => '#products', 'label' => 'Shop Now'],
        ['image' => 'caps.webp', 'alt' => 'Caps and hats', 'title' => 'Caps & Hats', 'description' => 'Custom caps and hats with embroidery options.', 'href' => '#gear', 'label' => 'Shop Now'],
        ['image' => 'bags.webp', 'alt' => 'Team bags', 'title' => 'Team Bags', 'description' => 'Duffel bags, drawstring bags, and travel gear.', 'href' => '#gear', 'label' => 'Shop Now'],
        ['image' => 'training.webp', 'alt' => 'Training wear', 'title' => 'Training Wear', 'description' => 'Practice shirts and performance apparel for workouts.', 'href' => '#products', 'label' => 'Shop Now'],
    ];
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">Most requested</span>
            <h2>Popular Custom Sportswear Categories</h2>
            <p>Our most requested products for teams, events, and fan gear.</p>
        </div>
        <div class="grid-4">
            @foreach($popularCategories as $category)
                <article class="product-card">
                    <img loading="lazy" decoding="async" src="{{ asset('storage/storefront/home/'.$category['image']) }}" alt="{{ $category['alt'] }}" width="650" height="450">
                    <div class="product-info">
                        <h3>{{ $category['title'] }}</h3>
                        <p>{{ $category['description'] }}</p>
                        <a class="link-red" href="{{ $category['href'] }}">{{ $category['label'] }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
