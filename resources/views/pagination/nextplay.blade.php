@php
    $totalItems = (int) $paginator->total();
    $firstItem = $totalItems > 0 ? (int) $paginator->firstItem() : 0;
    $lastItem = $totalItems > 0 ? (int) $paginator->lastItem() : 0;
    $currentPage = (int) $paginator->currentPage();
    $lastPage = max(1, (int) $paginator->lastPage());

    $resolvedItemName = trim((string) ($itemName ?? ''));

    if ($resolvedItemName === '') {
        $resolvedItemName = match (true) {
            request()->routeIs('products.*', 'categories.show', 'admin.products.*', 'admin.categories.products.*') => 'product',
            request()->routeIs('admin.categories.*') => 'category',
            request()->routeIs('account.orders.downloads') => 'download',
            request()->routeIs('admin.orders.*', 'account.orders.*') => 'order',
            request()->routeIs('admin.users.*') => 'user',
            request()->routeIs('admin.notifications.*') => 'notification',
            request()->routeIs('admin.returns.*', 'account.returns.*') => 'request',
            request()->routeIs('admin.coupons.*') => 'coupon',
            request()->routeIs('admin.payment-methods.*') => 'payment method',
            request()->routeIs('admin.shipping-methods.*') => 'shipping method',
            request()->routeIs('admin.production-methods.*') => 'production method',
            request()->routeIs('admin.homepage-slides.*') => 'slide',
            request()->routeIs('admin.attributes.*') => 'attribute',
            request()->routeIs('admin.size-option-groups.*', 'admin.training-vest-customization-options.*', 'admin.jersey-customization-options.*') => 'option',
            request()->routeIs('admin.rural-area-surcharges.*') => 'surcharge',
            request()->routeIs('admin.newsletter-subscribers.*') => 'subscriber',
            default => 'result',
        };
    }

    $itemLabel = \Illuminate\Support\Str::plural($resolvedItemName, $totalItems);

    if ($lastPage <= 10) {
        $pageNumbers = range(1, $lastPage);
    } elseif ($currentPage <= 4) {
        $pageNumbers = array_merge(range(1, 6), [$lastPage - 1, $lastPage]);
    } elseif ($currentPage >= $lastPage - 3) {
        $pageNumbers = array_merge([1, 2], range($lastPage - 5, $lastPage));
    } else {
        $pageNumbers = array_merge([1, 2], range($currentPage - 2, $currentPage + 2), [$lastPage - 1, $lastPage]);
    }

    $pageNumbers = array_values(array_unique(array_filter(
        $pageNumbers,
        static fn (int $page): bool => $page >= 1 && $page <= $lastPage
    )));
    sort($pageNumbers);

    $visiblePages = [];
    $previousVisiblePage = null;

    foreach ($pageNumbers as $pageNumber) {
        if ($previousVisiblePage !== null && $pageNumber - $previousVisiblePage > 1) {
            $visiblePages[] = 'ellipsis-'.$previousVisiblePage.'-'.$pageNumber;
        }

        $visiblePages[] = $pageNumber;
        $previousVisiblePage = $pageNumber;
    }
@endphp

<nav class="nextplay-pagination" role="navigation" aria-label="Pagination Navigation">
    <p class="nextplay-pagination__summary" aria-live="polite">
        @if($totalItems > 0)
            Showing {{ number_format($firstItem) }} to {{ number_format($lastItem) }} of {{ number_format($totalItems) }} {{ $itemLabel }}
        @else
            Showing 0 {{ $itemLabel }}
        @endif
    </p>

    @if($paginator->hasPages())
        <div class="nextplay-pagination__scroller">
            <div class="nextplay-pagination__controls">
                @if($paginator->onFirstPage())
                    <span class="nextplay-pagination__item nextplay-pagination__item--disabled" aria-disabled="true" aria-label="Previous page">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                    </span>
                @else
                    <a class="nextplay-pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                    </a>
                @endif

                @foreach($visiblePages as $page)
                    @if(is_string($page))
                        <span class="nextplay-pagination__item nextplay-pagination__item--ellipsis" aria-hidden="true">…</span>
                    @elseif($page === $currentPage)
                        <span class="nextplay-pagination__item nextplay-pagination__item--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="nextplay-pagination__item" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <a class="nextplay-pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 5 5 5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                    </a>
                @else
                    <span class="nextplay-pagination__item nextplay-pagination__item--disabled" aria-disabled="true" aria-label="Next page">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 5 5 5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/></svg>
                    </span>
                @endif
            </div>
        </div>
    @endif
</nav>
