@props(['category'])

<article
    class="np-all-category-card group"
    data-tags="{{ implode(' ', $category['tags']) }}"
    x-show="filter === 'all' || $el.dataset.tags.split(' ').includes(filter)"
    x-transition.opacity.duration.150ms
>
    <a
        href="{{ $category['url'] }}"
        class="np-all-category-card__media"
        aria-label="{{ $category['link_label'] }}"
    >
        <img
            loading="lazy"
            src="{{ $category['image'] }}"
            alt="{{ $category['alt'] }}"
            class="np-all-category-card__image"
            width="720"
            height="480"
        >
    </a>

    <div class="np-all-category-card__body">
        <div class="np-all-category-card__meta">
            @if ($category['parent_name'])
                <span class="np-all-category-card__parent">{{ $category['parent_name'] }}</span>
            @else
                <span class="np-all-category-card__parent">Product category</span>
            @endif

            @if (($category['product_count'] ?? 0) > 0)
                <span class="np-all-category-card__count">
                    {{ number_format((int) $category['product_count']) }}
                    <span class="sr-only">products</span>
                </span>
            @endif
        </div>

        <h3 class="np-all-category-card__title">
            <a href="{{ $category['url'] }}">{{ $category['title'] }}</a>
        </h3>

        @if (! empty($category['description']))
            <p class="np-all-category-card__description">{{ $category['description'] }}</p>
        @endif

        <a href="{{ $category['url'] }}" class="np-all-category-card__link">
            {{ $category['link_label'] }}
            <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
