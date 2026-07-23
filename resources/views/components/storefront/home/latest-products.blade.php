@props(['products' => [], 'section' => [], 'signature' => ''])

<x-storefront.home.product-collection
    :products="$products"
    :section="$section"
    section-id="latest-products"
    fallback-eyebrow="New arrivals"
    fallback-title="Latest"
    fallback-description="The latest created or updated active products appear here automatically."
    empty-message="No latest products are available yet."
    :slider="true"
    :live-refresh-url="route('home.latest-products')"
    :live-refresh-signature="$signature"
    :live-refresh-interval="5000"
/>
