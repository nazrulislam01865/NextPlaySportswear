@props(['title' => 'Quick Help', 'description' => 'Find what you need.', 'cards' => [], 'showHeading' => true])

<aside class="np-home-faq-aside" aria-labelledby="np-home-quick-help-title">
    @if($showHeading)
    <x-storefront.home.section-heading
        class="section-head np-home-quick-help-heading"
        align="left"
        :title="$title"
        :description="$description"
        title-id="np-home-quick-help-title"
    />
    @endif

    <div class="np-home-quick-help-grid">
        @foreach($cards as $card)
            <a class="np-home-quick-help-card" href="{{ $card['url'] }}">
                @if(filled($card['icon_path'] ?? null))
                    <img class="np-home-quick-help-icon" src="{{ \App\Support\PublicMedia::url((string) $card['icon_path']) }}" alt="" width="48" height="48" loading="lazy" decoding="async">
                @else
                    <x-storefront.home.quick-help-icon :name="(string) ($card['icon'] ?? '')" />
                @endif
                <span class="np-home-quick-help-card-title">{{ $card['title'] }}</span>
                <span class="np-home-quick-help-card-description">{{ $card['description'] }}</span>
                <svg class="np-home-quick-help-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
        @endforeach
    </div>
</aside>
