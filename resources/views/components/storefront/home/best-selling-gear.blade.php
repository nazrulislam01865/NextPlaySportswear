@props(['categories' => []])

<section id="gear">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Popular gear</span>
            <h2>Best-Selling Team Gear</h2>
        </div>
        <div class="gear-list">
            @forelse($categories as $category)
                <a class="gear-card" href="{{ $category['url'] }}" aria-label="Browse {{ $category['short_title'] }}">
                    <img loading="lazy" decoding="async" src="{{ $category['image'] }}" alt="{{ $category['alt'] }}" width="184" height="184">
                    <div>
                        <h3>{{ $category['short_title'] }}</h3>
                        <p>{{ $category['description'] }}</p>
                        <span class="link-red">{{ $category['link_label'] }}</span>
                    </div>
                </a>
            @empty
                <p>No featured catalog categories are available yet.</p>
            @endforelse
        </div>
    </div>
</section>
