@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section id="bulk" class="bulk-quote-home" aria-labelledby="bulkQuoteTitle">
    <div class="bulk-wrap">
        <div class="bulk-left">
            <p class="bulk-kicker">{{ $text('eyebrow', 'Team, School, League & Event') }}</p>
            <h2 id="bulkQuoteTitle" class="bulk-title">{{ $text('title', 'Ordering for a team, school, league, or event?') }}</h2>
            <p class="bulk-copy">{{ $text('description', 'Larger orders need a little more care. Share your quantity, sizes, artwork, delivery date, and shipping needs. Our team will review everything and send a clear bulk quote.') }}</p>

            <div class="bulk-actions">
                @if(filled($text('primary_label')))<a class="btn-primary" href="{{ $text('primary_url', route('quote.request')) }}">{{ $text('primary_label', 'Request Bulk Quote →') }}</a>@endif
                @if(filled($text('secondary_label')))<a class="btn-secondary" href="{{ $text('secondary_url', route('products.index')) }}">{{ $text('secondary_label', 'Explore Team Products') }}</a>@endif
                <p class="bulk-note">No long form here. Click request quote and complete the details on the quote page.</p>
            </div>
        </div>

        <div class="bulk-right">
            <div class="quote-panel">
                <div class="quote-panel-head">
                    <div class="quote-icon">⌁</div>
                    <div>
                        <h3>What we’ll ask for</h3>
                        <p>Prepare these details before requesting your quote. It helps us reply faster and more accurately.</p>
                    </div>
                </div>

                <div class="quote-list">
                    @foreach($items as $item)
                        <div class="quote-item">
                            <strong>{{ data_get($item, 'title') }}</strong>
                            <span>{{ data_get($item, 'description') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="quote-mini" aria-label="Bulk order examples">
                <div class="mini-card"><b>10+</b><span>Team uniforms</span></div>
                <div class="mini-card"><b>50+</b><span>Event apparel</span></div>
                <div class="mini-card"><b>500+</b><span>Promo orders</span></div>
            </div>
        </div>
    </div>
</section>
