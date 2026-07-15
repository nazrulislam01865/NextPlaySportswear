@props(['products' => [], 'section' => []])

<x-storefront.home.product-collection
    :products="$products"
    :section="$section"
    section-id="best-selling-products"
    fallback-eyebrow="Customer favorites"
    fallback-title="Best Selling"
    fallback-description="Products are ranked automatically from paid order quantities."
    empty-message="No best-selling products are available yet."
    :alternate="true"
/>
