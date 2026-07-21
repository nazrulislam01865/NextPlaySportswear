@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $cards = collect(is_iterable($categories) ? $categories : [])->filter(fn ($category) => is_array($category))->take(5)->values();
    $subtitle = $text('description', 'Built for teams. Designed to perform.');

    $visualPresets = [
        [
            'title' => 'Performance Apparel',
            'description' => 'Custom performance apparel for active brands, teams, events, and promotional programs.',
            'image' => asset('images/storefront/home/best-selling/performance-apparel.webp'),
            'alt' => 'Custom performance apparel including jerseys, shirts, pullovers, and hoodies',
        ],
        [
            'title' => 'Accessories',
            'description' => 'Custom accessories that add value to teamwear, fanwear, and promotional campaigns.',
            'image' => asset('images/storefront/home/best-selling/accessories.webp'),
            'alt' => 'Custom sports accessories including socks, towel, and wristbands',
        ],
        [
            'title' => 'Bags',
            'description' => 'Custom sports and promotional bags for teams, schools, events, and branded merchandise programs.',
            'image' => asset('images/storefront/home/best-selling/bags.webp'),
            'alt' => 'Custom sports bags including duffel bags, backpacks, and drawstring bags',
        ],
        [
            'title' => 'Drinkware',
            'description' => 'Custom drinkware for teams, gyms, schools, giveaways, and retail promotions.',
            'image' => asset('images/storefront/home/best-selling/drinkware.webp'),
            'alt' => 'Custom drinkware bottles, tumblers, and shaker cups',
        ],
        [
            'title' => 'Headwear',
            'description' => 'Custom headwear for teams, events, merch collections, and corporate branding.',
            'image' => asset('images/storefront/home/best-selling/headwear.webp'),
            'alt' => 'Custom headwear including caps and beanies',
        ],
    ];

    if ($cards->count() < count($visualPresets)) {
        for ($slot = $cards->count(); $slot < count($visualPresets); $slot++) {
            $cards->push([
                'title' => $visualPresets[$slot]['title'],
                'short_title' => $visualPresets[$slot]['title'],
                'description' => $visualPresets[$slot]['description'],
                'url' => route('products.index'),
            ]);
        }
    }
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
                        $preset = $visualPresets[$index] ?? [];
                        $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                        $isFeatured = $index === 0;
                        $title = (string) ($preset['title'] ?? ($category['short_title'] ?? $category['title'] ?? 'Category'));
                        $description = (string) ($preset['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) ($category['description'] ?? 'Shop custom team gear for clubs, schools, events, and branded programs.')), $isFeatured ? 155 : 125));
                        $image = (string) ($preset['image'] ?? ($category['image'] ?? asset('images/category-placeholder.svg')));
                        $alt = (string) ($preset['alt'] ?? ($category['alt'] ?? $title.' category image'));
                        $url = (string) ($category['url'] ?? route('products.index'));
                        $label = 'Shop Category';
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
                                width="{{ $isFeatured ? 770 : 394 }}"
                                height="{{ $isFeatured ? 278 : 212 }}"
                            >
                        </span>
                        <span class="np-best-gear-card__divider" aria-hidden="true"></span>
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
