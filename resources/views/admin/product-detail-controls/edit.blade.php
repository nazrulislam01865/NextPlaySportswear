<x-layouts.admin
    title="Product Detail Controls"
    subtitle="Manage the shared customer-facing labels, buttons, size information, artwork, shipping, delivery text and icons used across product detail pages."
>
    <div class="mx-auto max-w-7xl space-y-5">
        <section class="np-pdui-page-intro">
            <div>
                <p class="np-pdui-eyebrow">Centralized storefront controls</p>
                <h2>One configuration for all product detail pages</h2>
                <p>Changes saved here are reused across products. Product-specific values such as sizes, minimum quantity, sample availability and artwork upload limits remain on the individual product form.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-white">View Products</a>
        </section>

        <form method="POST" action="{{ route('admin.product-detail-controls.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <x-admin.product-storefront-ui-settings :settings="$settings" />

            <div class="np-pdui-savebar">
                <div>
                    <strong>Apply globally</strong>
                    <span>Saving updates these shared controls for every product detail page.</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-white">Cancel</a>
                    @if($canManage)
                        <button type="submit" class="btn btn-primary">Save Product Detail Controls</button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
