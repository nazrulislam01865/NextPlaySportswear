@props(['products' => []])

@php
    $products = collect($products)
        ->filter(fn ($product) => is_array($product) && filled($product['url'] ?? null))
        ->take(5)
        ->values();
@endphp

@if ($products->isNotEmpty())
    <section class="np-related-products" aria-labelledby="related-products-title">
        <div class="site-container">
            <header class="np-related-products__header">
                <h2 id="related-products-title">Related Products</h2>
                <a href="{{ route('products.index') }}">View All Products</a>
            </header>

            {{--
                Related products intentionally use the same reusable product-card component as
                the homepage, category pages, All Products page, and other storefront sections.
                This keeps image sizing, typography, pricing, wishlist behavior, activity data,
                and calls to action synchronized automatically whenever the shared card changes.
            --}}
            <div class="np-related-products__grid np-product-listing-grid">
                @foreach ($products as $relatedProduct)
                    <x-storefront.product-card :product="$relatedProduct" />
                @endforeach
            </div>
        </div>
    </section>

    @once
        <style>
            .np-related-products {
                padding: clamp(3rem, 5vw, 5rem) 0;
                border-top: 1px solid var(--np-color-border);
                background: #fff;
            }

            .np-related-products__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: clamp(1.25rem, 2vw, 1.75rem);
            }

            .np-related-products__header h2 {
                margin: 0;
                color: var(--np-color-primary);
                font-family: var(--np-font-heading);
                font-size: clamp(1.8rem, 3vw, 2.75rem);
                font-weight: 800;
                line-height: 1;
                letter-spacing: -.025em;
                text-transform: uppercase;
            }

            .np-related-products__header > a {
                display: inline-flex;
                flex-shrink: 0;
                white-space: nowrap;
                min-height: 3rem;
                align-items: center;
                justify-content: center;
                padding: .7rem 1.15rem;
                border: 1px solid var(--np-color-border);
                border-radius: .75rem;
                background: #fff;
                color: var(--np-color-primary);
                font-size: .82rem;
                font-weight: 800;
                transition: border-color .16s ease, color .16s ease, background .16s ease;
            }

            .np-related-products__header > a:hover,
            .np-related-products__header > a:focus-visible {
                border-color: var(--np-color-primary);
                background: var(--np-color-primary);
                color: #fff;
                outline: none;
            }

            .np-related-products__grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                align-items: stretch;
                gap: clamp(1rem, 1.4vw, 1.35rem);
            }

            @media (min-width: 640px) {
                .np-related-products__grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1180px) {
                .np-related-products__grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            @media (max-width: 560px) {
                .np-related-products__header {
                    align-items: center;
                    gap: .75rem;
                }

                .np-related-products__header h2 {
                    min-width: 0;
                    font-size: clamp(1.125rem, 4.5vw, 1.5rem);
                    line-height: 1.2;
                    text-transform: none;
                }

                .np-related-products__header > a {
                    min-height: 2.75rem;
                    padding: .5rem .65rem;
                    font-size: .72rem;
                }
            }
        </style>
    @endonce
@endif
