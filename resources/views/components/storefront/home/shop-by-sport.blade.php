@props(['sports' => []])

<section id="sports" class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Find your sport</span>
            <h2>Shop by Sport</h2>
            <p>Active sport categories from the admin catalog appear here automatically.</p>
        </div>
        <div class="grid-6">
            @forelse($sports as $sport)
                <a class="sport-card" href="{{ $sport['url'] }}" aria-label="Shop {{ $sport['short_title'] }} Gear">
                    <img loading="lazy" decoding="async" src="{{ $sport['image'] }}" alt="{{ $sport['alt'] }}" width="420" height="260">
                    <h3>{{ $sport['title'] }}</h3>
                    <span class="link-red">Shop {{ $sport['short_title'] }} Gear</span>
                </a>
            @empty
                <p>No active sport categories are available yet.</p>
            @endforelse
        </div>
    </div>
</section>
