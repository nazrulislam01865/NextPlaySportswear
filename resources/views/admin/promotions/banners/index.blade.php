@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-sale-banners.css') }}?v={{ file_exists(public_path('css/admin-sale-banners.css')) ? filemtime(public_path('css/admin-sale-banners.css')) : 1 }}">
@endpush

@push('admin-page-scripts')
    <script src="{{ asset('js/admin-sale-banners.js') }}?v={{ file_exists(public_path('js/admin-sale-banners.js')) ? filemtime(public_path('js/admin-sale-banners.js')) : 1 }}"></script>
@endpush

<x-layouts.admin
    title="Sale Banners"
    eyebrow="Promotions / Banners"
    subtitle="Manage promotional banners to feature campaigns across your store."
>
    @php
        $placementLabels = [
            \App\Models\SaleBanner::PLACEMENT_SALE_TOP => 'Sale list: top',
            \App\Models\SaleBanner::PLACEMENT_PRODUCT_TOP => 'Product list: above results',
            \App\Models\SaleBanner::PLACEMENT_PRODUCT_AFTER_ROW_2 => 'Product list: after row 2',
            \App\Models\SaleBanner::PLACEMENT_CATEGORY_TOP => 'Category list: top',
        ];
        $selected = $selectedBanner;
        $formAction = $selected ? route('admin.promotions.banners.update', $selected) : route('admin.promotions.banners.store');
    @endphp

    <div class="np-banner-page" x-data="adminSaleBannerEditor(@js($initialBanner))">
        <div class="np-banner-page__topbar">
            <a href="{{ route('admin.promotions.banners.index', ['new' => 1]) }}" class="btn btn-red np-banner-add-button">
                <span aria-hidden="true">＋</span> Add Banner
            </a>
        </div>

        <div class="np-banner-admin-grid">
            <div class="np-banner-admin-left">
                @include('admin.promotions.banners._banner-list')
                @include('admin.promotions.banners._placement-preview')
            </div>

            @include('admin.promotions.banners._editor')
        </div>
    </div>
</x-layouts.admin>
