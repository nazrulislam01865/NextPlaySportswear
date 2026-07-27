@props(['sports' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key))
        ? (string) data_get($section, $key)
        : $fallback;
    $sportItems = collect($sports)->filter(fn ($sport): bool => is_array($sport))->values();
    $sportCount = $sportItems->count();
    $viewAllClasses = collect([
        'np-shop-sport__footer',
        $sportCount > 4 ? 'np-shop-sport__footer--more-2' : null,
        $sportCount > 8 ? 'np-shop-sport__footer--more-4' : null,
        $sportCount > 10 ? 'np-shop-sport__footer--more-5' : null,
        $sportCount > 14 ? 'np-shop-sport__footer--more-7' : null,
    ])->filter()->implode(' ');
@endphp

<section
    id="sports"
    class="np-shop-sport"
    aria-labelledby="np-shop-sport-title"
    x-data="{ expanded: false }"
    x-init="$el.classList.add('is-collapsible')"
    :class="{ 'is-expanded': expanded }"
    x-cloak
>
    <div class="np-shop-sport__inner">
        <header class="np-shop-sport__header">
            <p class="np-shop-sport__eyebrow">{{ $text('eyebrow', 'Find your sport') }}</p>
            <h2 id="np-shop-sport-title" class="np-shop-sport__title">{{ $text('title', 'Shop by Sport') }}</h2>
            <p class="np-shop-sport__description">{{ $text('description', 'Browse uniforms, apparel and gear by sport.') }}</p>
        </header>

        @if($sportItems->isNotEmpty())
            <div id="np-shop-sport-grid" class="np-shop-sport__grid">
                @foreach($sportItems as $sport)
                    @php
                        $title = trim((string) ($sport['title'] ?? $sport['short_title'] ?? 'Sport'));
                        $count = max(0, (int) ($sport['product_count'] ?? 0));
                        $countLabel = number_format($count).' '.($count === 1 ? 'item' : 'items');
                    @endphp

                    <a
                        class="np-shop-sport-card"
                        href="{{ $sport['url'] ?? route('categories.index') }}"
                        aria-label="Browse {{ $title }}, {{ $countLabel }}"
                    >
                        <span class="np-shop-sport-card__media">
                            <img
                                loading="lazy"
                                decoding="async"
                                src="{{ $sport['image'] ?? asset('images/category-placeholder.svg') }}"
                                alt="{{ $sport['alt'] ?? $title }}"
                                width="420"
                                height="420"
                            >
                        </span>

                        <span class="np-shop-sport-card__body">
                            <strong class="np-shop-sport-card__title">{{ $title }}</strong>
                            <span class="np-shop-sport-card__meta">
                                <span>{{ $countLabel }}</span>
                                <svg class="np-shop-sport-card__arrow" viewBox="0 0 30 30" aria-hidden="true">
                                    <path d="M5 15h18M17 8l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>

            @if($sportCount > 4)
                <div class="{{ $viewAllClasses }}">
                    <button
                        type="button"
                        class="np-shop-sport__view-all"
                        @click="expanded = !expanded"
                        :aria-expanded="expanded.toString()"
                        aria-controls="np-shop-sport-grid"
                    >
                        <span x-text="expanded ? 'Show Less Sports' : 'View All Sports'">View All Sports</span>
                        <svg :class="{ 'is-expanded': expanded }" viewBox="0 0 26 26" aria-hidden="true">
                            <path d="M4 13h16M15 7l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            @endif
        @else
            <p class="np-shop-sport__empty">No active sport categories are available yet.</p>
        @endif
    </div>
</section>
