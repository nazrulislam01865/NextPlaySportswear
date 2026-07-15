@props(['products' => [], 'section' => []])

<x-storefront.home.product-collection
    :products="$products"
    :section="$section"
    section-id="products"
    fallback-eyebrow="Shop online"
    fallback-title="Featured Products"
    fallback-description="Products marked as featured by the admin appear here automatically."
    empty-message="No active featured products are available yet."
    :alternate="true"
/>
