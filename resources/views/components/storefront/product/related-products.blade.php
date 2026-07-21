@props(['products' => []])

@php
    $products = collect($products)->filter(fn ($product) => is_array($product))->take(4)->values();
@endphp

@if($products->isNotEmpty())
    <section class="np-related-products" aria-labelledby="related-products-title">
        <div class="site-container">
            <header class="np-related-products__header">
                <h2 id="related-products-title">Related Products</h2>
                <a href="{{ route('products.index') }}">View All Products</a>
            </header>

            <div class="np-related-products__grid">
                @foreach($products as $relatedProduct)
                    <article class="np-related-product-card">
                        <a href="{{ $relatedProduct['url'] ?? '#' }}" class="np-related-product-card__image" aria-label="View {{ $relatedProduct['title'] ?? 'product' }}">
                            <img
                                src="{{ $relatedProduct['image'] ?? asset('images/product-placeholder.svg') }}"
                                alt="{{ $relatedProduct['alt'] ?? $relatedProduct['title'] ?? 'Related product' }}"
                                loading="lazy"
                                width="640"
                                height="640"
                            >
                        </a>
                        <div class="np-related-product-card__body">
                            @if(filled($relatedProduct['category'] ?? null))
                                <p>{{ $relatedProduct['category'] }}</p>
                            @endif
                            <h3><a href="{{ $relatedProduct['url'] ?? '#' }}">{{ $relatedProduct['title'] ?? 'Product' }}</a></h3>
                            <strong>{{ $relatedProduct['price'] ?? '' }}</strong>
                            <a class="np-related-product-card__button" href="{{ $relatedProduct['url'] ?? '#' }}">View Product</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @once
        <style>
            .np-related-products {
                padding: clamp(2.8rem, 5vw, 4.8rem) 0;
                background: #fff;
                border-top: 1px solid #edf2f7;
            }

            .np-related-products__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1.2rem;
            }

            .np-related-products__header h2 {
                margin: 0;
                color: #061744;
                font-family: var(--font-display, inherit);
                font-size: clamp(1.45rem, 2.5vw, 2.1rem);
                font-weight: 780;
                letter-spacing: -.025em;
                text-transform: uppercase;
            }

            .np-related-products__header > a {
                display: inline-flex;
                min-height: 2.35rem;
                align-items: center;
                justify-content: center;
                padding: .55rem .85rem;
                border: 1px solid #d7e0eb;
                border-radius: .55rem;
                color: #061744;
                font-size: .72rem;
                font-weight: 750;
                transition: border-color .16s ease, color .16s ease, background .16s ease;
            }

            .np-related-products__header > a:hover {
                border-color: #061744;
                background: #061744;
                color: #fff;
            }

            .np-related-products__grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1rem;
            }

            .np-related-product-card {
                display: flex;
                min-width: 0;
                flex-direction: column;
                overflow: hidden;
                border: 1px solid #dfe7f1;
                border-radius: .85rem;
                background: #fff;
                box-shadow: 0 10px 28px rgba(6, 23, 68, .05);
                transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            }

            .np-related-product-card:hover {
                transform: translateY(-2px);
                border-color: #c5d5e8;
                box-shadow: 0 16px 34px rgba(6, 23, 68, .09);
            }

            .np-related-product-card__image {
                display: block;
                aspect-ratio: 1 / 1;
                overflow: hidden;
                border-bottom: 1px solid #e7edf4;
                background: #f7f9fc;
            }

            .np-related-product-card__image img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: contain;
                transition: transform .24s ease;
            }

            .np-related-product-card:hover .np-related-product-card__image img {
                transform: scale(1.025);
            }

            .np-related-product-card__body {
                display: flex;
                flex: 1;
                flex-direction: column;
                padding: .9rem;
            }

            .np-related-product-card__body > p {
                overflow: hidden;
                margin: 0 0 .35rem;
                color: #16aeca;
                font-size: .63rem;
                font-weight: 800;
                letter-spacing: .1em;
                text-overflow: ellipsis;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .np-related-product-card h3 {
                display: -webkit-box;
                overflow: hidden;
                min-height: 3.55rem;
                margin: 0;
                color: #061744;
                font-size: .88rem;
                font-weight: 720;
                line-height: 1.35;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 3;
            }

            .np-related-product-card h3 a:hover {
                color: #e91d33;
            }

            .np-related-product-card__body > strong {
                margin-top: .6rem;
                color: #061744;
                font-size: .78rem;
                font-weight: 800;
            }

            .np-related-product-card__button {
                display: inline-flex;
                min-height: 2.25rem;
                align-items: center;
                justify-content: center;
                margin-top: .75rem;
                padding: .55rem .7rem;
                border: 1px solid #d7e0eb;
                border-radius: .5rem;
                color: #061744;
                font-size: .72rem;
                font-weight: 750;
                transition: border-color .16s ease, background .16s ease, color .16s ease;
            }

            .np-related-product-card__button:hover {
                border-color: #061744;
                background: #061744;
                color: #fff;
            }

            @media (max-width: 1024px) {
                .np-related-products__grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 560px) {
                .np-related-products__header {
                    align-items: flex-start;
                }

                .np-related-products__grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }
        </style>
    @endonce
@endif
