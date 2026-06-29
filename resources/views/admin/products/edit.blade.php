<x-layouts.admin
    :title="'Edit: '.$product->name"
    eyebrow="Edit Product"
    subtitle="Update product details, pricing, options, fulfillment, and storefront content."
    :compact-header="true"
    :storefront-url="route('products.show', $product->slug)"
>
    @include('admin.products._form')
</x-layouts.admin>
