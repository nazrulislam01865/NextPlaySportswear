<x-storefront.account.page
    :seo="$seo"
    title="MY ORDERS"
    subtitle="Track, filter, and manage every order in one place."
    :account="$account"
    :navigation="$navigation"
>
    <div class="np-orders-history-page">
        <section class="np-orders-summary-panel">
            <div class="np-orders-summary-intro">
                <div class="np-orders-summary-intro__icon"><x-storefront.account.icon name="order-history" :size="31" /></div>
                <div>
                    <h2>MY ORDERS</h2>
                    <p>View, track, and manage all your orders, payments, and deliveries.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    <span>Start New Order</span>
                    <x-storefront.account.icon name="arrow-right" :size="18" />
                </a>
            </div>

            <div class="np-orders-summary-stats">
                <x-storefront.account.dashboard-stat icon="order-history" :value="$account['stats']['total_orders']" label="Total Orders" tone="orange" />
                <x-storefront.account.dashboard-stat icon="truck" :value="$account['stats']['open_orders']" label="Open Orders" tone="blue" />
                <x-storefront.account.dashboard-stat icon="payment" :value="$account['stats']['awaiting_payment']" label="Awaiting Payment" tone="purple" />
                <x-storefront.account.dashboard-stat icon="rewards" :value="$account['stats']['reward_balance_display']" label="Rewards Balance" tone="orange" />
            </div>
        </section>

        <form method="GET" class="np-orders-filter-bar" id="orders-filter-form">
            <label class="np-orders-filter-bar__search">
                <span class="sr-only">Search orders</span>
                <x-storefront.account.icon name="search" :size="19" />
                <input name="q" value="{{ request('q') }}" placeholder="Search order number or product..." autocomplete="off">
            </label>

            <label class="np-orders-filter-bar__select">
                <span class="sr-only">Order status</span>
                <select name="status">
                    <option value="">All Order Statuses</option>
                    @foreach($orderStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="np-orders-filter-bar__select">
                <span class="sr-only">Payment status</span>
                <select name="payment_status">
                    <option value="">All Payment Statuses</option>
                    @foreach($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="np-orders-filter-bar__select np-orders-filter-bar__select--date">
                <x-storefront.account.icon name="calendar" :size="18" />
                <span class="sr-only">Date range</span>
                <select name="period">
                    <option value="">Date Range</option>
                    @foreach($periodOptions as $key => $label)
                        <option value="{{ $key }}" @selected(request('period') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <input type="hidden" name="sort" value="{{ request('sort', 'recent') }}">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>

        <div class="np-orders-list-heading">
            <h2>{{ $orders->total() }} Order{{ $orders->total() === 1 ? '' : 's' }}</h2>
            <form method="GET" class="np-orders-sort-form">
                @foreach(['q', 'status', 'payment_status', 'period'] as $field)
                    @if(request()->filled($field))<input type="hidden" name="{{ $field }}" value="{{ request($field) }}">@endif
                @endforeach
                <label>
                    <span>Sort by:</span>
                    <select name="sort" onchange="this.form.submit()">
                        @foreach($sortOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('sort', 'recent') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="np-orders-history-list">
            @forelse($orders as $order)
                <x-storefront.account.orders.history-card :order="$order" />
            @empty
                <div class="np-orders-empty-state">
                    <h3>No matching orders</h3>
                    <p>Try clearing the filters or browse products to start a new order.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">Browse Products</a>
                </div>
            @endforelse
        </div>

        <div class="np-orders-pagination">
            <span>Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</span>
            <nav class="np-orders-pagination__pages" aria-label="Orders pagination">
                @if($orders->onFirstPage())
                    <span class="is-disabled" aria-hidden="true">‹</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
                @endif

                @php
                    $pageStart = max(1, $orders->currentPage() - 2);
                    $pageEnd = min($orders->lastPage(), $orders->currentPage() + 2);
                @endphp
                @for($page = $pageStart; $page <= $pageEnd; $page++)
                    @if($page === $orders->currentPage())
                        <span class="is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $orders->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
                @else
                    <span class="is-disabled" aria-hidden="true">›</span>
                @endif
            </nav>
        </div>
    </div>
</x-storefront.account.page>
