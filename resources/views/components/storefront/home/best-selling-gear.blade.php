@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $cards = collect(is_iterable($categories) ? $categories : [])->filter(fn ($category) => is_array($category))->take(5)->values();
    $subtitle = $text('description', 'Built for teams. Designed to perform.');
@endphp

<section id="gear" class="np-best-gear-section" aria-labelledby="best-selling-gear-heading">
    <div class="container">
        <div class="np-best-gear-head">
            <span class="np-best-gear-eyebrow">{{ $text('eyebrow', 'POPULAR GEAR') }}</span>
            <h2 id="best-selling-gear-heading">{{ $text('title', 'BEST-SELLING TEAM GEAR') }}</h2>
            @if(filled($subtitle))
                <p>{{ $subtitle }}</p>
            @endif
        </div>

        @if($cards->isNotEmpty())
            <div class="np-best-gear-layout" aria-label="Best-selling team gear categories">
                @foreach($cards as $index => $category)
                    @php
                        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                        $isFeatured = $index === 0;
                        $title = (string) ($category['short_title'] ?? $category['title'] ?? 'Category');
                        $description = \Illuminate\Support\Str::limit(strip_tags((string) ($category['description'] ?? 'Shop custom team gear for clubs, schools, events, and branded programs.')), $isFeatured ? 155 : 125);
                        $image = (string) ($category['image'] ?? asset('images/category-placeholder.svg'));
                        $alt = (string) ($category['alt'] ?? $title.' category image');
                        $url = (string) ($category['url'] ?? route('products.index'));
                        $label = (string) ($category['link_label'] ?? 'Shop Category');
                    @endphp

                    <a
                        href="{{ $url }}"
                        class="np-best-gear-card {{ $isFeatured ? 'np-best-gear-card--featured' : 'np-best-gear-card--supporting' }}"
                        aria-label="{{ $label }}: {{ $title }}"
                    >
                        <span class="np-best-gear-card__number" aria-hidden="true">{{ $number }}</span>
                        <span class="np-best-gear-card__media">
                            <img
                                loading="lazy"
                                decoding="async"
                                src="{{ $image }}"
                                alt="{{ $alt }}"
                                width="{{ $isFeatured ? 760 : 520 }}"
                                height="{{ $isFeatured ? 360 : 300 }}"
                            >
                        </span>
                        <span class="np-best-gear-card__content">
                            <span class="np-best-gear-card__title">{{ $title }}</span>
                            <span class="np-best-gear-card__description">{{ $description }}</span>
                            <span class="np-best-gear-card__link">{{ $label }} <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="np-best-gear-empty">
                <p>No best-selling gear categories are available yet.</p>
            </div>
        @endif
    </div>
</section>
