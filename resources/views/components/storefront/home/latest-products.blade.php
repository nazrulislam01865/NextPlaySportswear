@props(['products' => [], 'section' => []])

<x-storefront.home.product-collection
    :products="$products"
    :section="$section"
    section-id="latest-products"
    fallback-eyebrow="New arrivals"
    fallback-title="Latest"
    fallback-description="The newest active products appear here automatically."
    empty-message="No latest products are available yet."
/>
