{{-- Prototype navigation for bulk quote details only. Other admin pages retain their usual sidebar. --}}
@php
    $adminUser = auth('admin')->user();
    $can = static fn (string $permission): bool => (bool) ($adminUser?->canAdmin($permission) ?? false);
@endphp
<nav data-admin-sidebar-nav class="np-bq-sidebar-nav" aria-label="Bulk quotation navigation">
    @if($can('dashboard.view'))
        <a class="np-bq-side-link" href="{{ route('admin.dashboard') }}"><x-admin.bulk-quote-icon name="home" />Dashboard</a>
    @endif

    @if($can('customization.view') || $can('products.view') || $can('categories.view'))
        <p class="np-bq-side-heading">Master Data</p>
        <details class="np-bq-side-disclosure" data-sidebar-disclosure>
            <summary class="np-bq-side-link"><x-admin.bulk-quote-icon name="cube" /><span>Master Data</span><x-admin.bulk-quote-icon name="chevron" class="np-bq-side-chevron" /></summary>
            <div class="np-bq-side-submenu">
                @if($can('products.view'))<a href="{{ route('admin.products.index') }}">Products</a>@endif
                @if($can('categories.view'))<a href="{{ route('admin.categories.index') }}">Categories</a>@endif
                @if($can('customization.view'))<a href="{{ route('admin.personalization-templates.index') }}">Personalization</a>@endif
            </div>
        </details>
    @endif

    @if($can('orders.view') || $can('returns.view') || $can('customers.view') || $can('coupons.view') || $can('coupons.manage'))
        <p class="np-bq-side-heading">Commerce</p>
        @if($can('orders.view'))
            <a class="np-bq-side-link" href="{{ route('admin.orders.index') }}"><x-admin.bulk-quote-icon name="bag" />Orders</a>
            <a class="np-bq-side-link is-selected" aria-current="page" href="{{ route('admin.bulk-quotes.index') }}"><x-admin.bulk-quote-icon name="file" />Bulk Quote Requests</a>
        @endif
        @if($can('returns.view'))<a class="np-bq-side-link" href="{{ route('admin.returns.index') }}"><x-admin.bulk-quote-icon name="refresh" />Returns &amp; Exchanges</a>@endif
        @if($can('customers.view'))
            <a class="np-bq-side-link" href="{{ route('admin.customers.index') }}"><x-admin.bulk-quote-icon name="user" />Customers</a>
            <a class="np-bq-side-link" href="{{ route('admin.reward-program.edit') }}"><x-admin.bulk-quote-icon name="star" />Rewards Programme</a>
        @endif
        @if($can('coupons.manage'))<a class="np-bq-side-link" href="{{ route('admin.promotions.sales.index') }}"><x-admin.bulk-quote-icon name="tag" />Promotions</a>@endif
        @if($can('coupons.view'))<a class="np-bq-side-link" href="{{ route('admin.coupons.index') }}"><x-admin.bulk-quote-icon name="ticket" />Discounts &amp; Coupons</a>@endif
    @endif

    @if($can('storefront_branding.view'))
        <p class="np-bq-side-heading">Store</p>
        <details class="np-bq-side-disclosure" data-sidebar-disclosure>
            <summary class="np-bq-side-link"><x-admin.bulk-quote-icon name="store" /><span>Manage Store</span><x-admin.bulk-quote-icon name="chevron" class="np-bq-side-chevron" /></summary>
            <div class="np-bq-side-submenu"><a href="{{ route('admin.storefront-branding.edit') }}">Storefront Branding</a></div>
        </details>
    @endif
</nav>
