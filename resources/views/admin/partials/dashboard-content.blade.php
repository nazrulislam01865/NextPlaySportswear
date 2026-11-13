    <div class="np-dashboard" data-dashboard-days="{{ $days }}" data-dashboard-chart="{{ json_encode($chart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" data-dashboard-currency="{{ $currency }}">
        <div class="np-dash-intro">
            <div>
                <div class="np-dash-overline">Store Performance <span>● Live store data</span></div>
                <h1>Welcome back, {{ \Illuminate\Support\Str::before(auth('admin')->user()?->name ?? 'Admin', ' ') }}! <span aria-hidden="true">👋</span></h1>
                <p>Here's what's happening across NextPlay Sportswear. Explore and drill into any section.</p>
            </div>
            <div class="np-dash-intro-actions">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="np-dash-range" data-dashboard-period-form>
                    <x-admin.dashboard-icon name="calendar" :size="14" />
                    <select name="days" aria-label="Dashboard reporting period" >
                        @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $option => $label)
                            <option value="{{ $option }}" @selected($days === $option)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="np-dash-screen-reader-only">Apply reporting period</button>
                </form>
                @if($canViewOrders)
                    <a class="np-dash-action np-dash-action-secondary" href="{{ route('admin.dashboard', ['days' => $days, 'export' => 'csv']) }}"><x-admin.dashboard-icon name="download" :size="14" />Export report</a>
                @endif
                @if($canManageProducts)
                    <a class="np-dash-action np-dash-action-primary" href="{{ route('admin.products.create') }}"><x-admin.dashboard-icon name="plus" :size="14" />Add product</a>
                @endif
            </div>
        </div>

        <div class="np-dash-workspace">
            <div class="np-dash-work-main">
                <section class="np-dash-kpis" aria-label="Key performance indicators">
                    @foreach([
                        ['Total revenue', number_format($revenue, 2), $currency, 'dollar', 'peach', 'revenue', 'Paid order revenue'],
                        ['Total orders', number_format($totalOrders), '', 'cart', 'mint', 'orders', 'Orders in period'],
                        ['Total customers', number_format($customerCount), '', 'users', 'blue', 'customers', 'Customer accounts'],
                        ['Bulk quote requests', number_format($totalQuotes), '', 'file', 'purple', 'quotes', 'Quotes in period'],
                    ] as $kpi)
                        <div class="np-dash-kpi np-dash-kpi-{{ $kpi[4] }}">
                            <div class="np-dash-kpi-top"><span>{{ $kpi[0] }}</span><span class="np-dash-kpi-icon"><x-admin.dashboard-icon :name="$kpi[3]" :size="17" /></span></div>
                            <div class="np-dash-kpi-value">@if($kpi[2] !== '')<small>{{ $kpi[2] }}</small>@endif{{ $kpi[1] }}</div>
                            <div class="np-dash-kpi-bottom"><span>{{ $kpi[6] }}</span><svg aria-hidden="true" viewBox="0 0 100 28" preserveAspectRatio="none" data-sparkline="{{ $kpi[5] }}"><polyline points="0,23 20,20 40,21 60,15 80,17 100,9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg></div>
                        </div>
                    @endforeach
                </section>

                <div class="np-dash-analytics-row">
                    <section class="np-dash-panel np-dash-sales" aria-labelledby="np-sales-heading">
                        <div class="np-dash-panel-heading">
                            <div><h2 id="np-sales-heading">Sales analytics</h2><p>Interactive performance trends for your store</p></div>
                            <div class="np-dash-tabs" role="group" aria-label="Sales chart metric">
                                <button type="button" class="is-active" aria-pressed="true" data-chart-tab="revenue">Revenue</button>
                                <button type="button" aria-pressed="false" data-chart-tab="orders">Orders</button>
                                <button type="button" aria-pressed="false" data-chart-tab="customers">Customers</button>
                            </div>
                        </div>
                        <div class="np-dash-sales-total"><span data-chart-total>{{ $currency }} {{ number_format($revenue, 2) }}</span><span class="np-dash-positive">{{ $days }} days</span></div>
                        <div class="np-dash-chart" role="img" aria-label="Sales trend for the selected reporting period">
                            <div class="np-dash-chart-plot"><svg viewBox="0 0 530 160" preserveAspectRatio="none" data-sales-svg aria-hidden="true">
                                <defs><linearGradient id="npSalesFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#e65b37" stop-opacity=".20"/><stop offset="100%" stop-color="#e65b37" stop-opacity="0"/></linearGradient></defs>
                                <path d="M0 30 H530 M0 72 H530 M0 114 H530 M0 156 H530" fill="none" stroke="#e8edf4" stroke-width="1"/>
                                <path data-sales-fill fill="url(#npSalesFill)" d=""/><polyline data-sales-line fill="none" stroke="#e65b37" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" points="" />
                            </svg></div>
                            <div class="np-dash-chart-axis"><span>{{ \Carbon\Carbon::parse($chart[0]['day'] ?? now())->format('M d') }}</span><span>{{ \Carbon\Carbon::parse($chart[(int) floor(($days - 1) / 3)]['day'] ?? now())->format('M d') }}</span><span>{{ \Carbon\Carbon::parse($chart[(int) floor(2 * ($days - 1) / 3)]['day'] ?? now())->format('M d') }}</span><span>{{ \Carbon\Carbon::parse($chart[$days - 1]['day'] ?? now())->format('M d') }}</span></div>
                        </div>
                        <div class="np-dash-chart-foot"><span>● <span data-chart-summary>Paid order revenue</span></span><span>Hover chart for daily details</span></div>
                        <div class="np-dash-chart-tooltip" data-chart-tooltip aria-live="polite"></div>
                    </section>
                    <section class="np-dash-panel np-dash-order-status" aria-labelledby="np-status-heading">
                        <div class="np-dash-panel-heading"><div><h2 id="np-status-heading">Order status</h2><p>Click a segment to filter orders</p></div><button type="button" class="np-dash-clear" data-clear-orders>Clear ×</button></div>
                        @php
                            $ringTotal = max(1, array_sum($statusGroups));
                            $ringColors = ['#3d94ef', '#16b985', '#f5b848', '#f36e70'];
                            $ringStop = 0;
                            $ringSegments = [];
                            foreach(array_values($statusGroups) as $index => $amount) {
                                $next = $ringStop + ($amount / $ringTotal * 100);
                                $ringSegments[] = $ringColors[$index].' '.round($ringStop, 3).'% '.round($next, 3).'%';
                                $ringStop = $next;
                            }
                        @endphp
                        <div class="np-dash-ring" role="button" tabindex="0" aria-label="{{ $totalOrders }} orders by status. Activate a segment to filter recent orders." data-ring-counts='@json(array_values($statusGroups))' style="--np-dash-ring:{{ $totalOrders ? implode(', ', $ringSegments) : '#e5eaf1 0% 100%' }}"><div><strong>{{ number_format($totalOrders) }}</strong><span>Total orders</span></div></div>
                        <div class="np-dash-ring-legend">
                            @foreach($statusGroups as $label => $value)
                                <button type="button" data-status-group="{{ strtolower($label) }}"><i style="background:{{ $ringColors[$loop->index] }}"></i><span>{{ $label }}</span><b>{{ number_format($value) }}</b></button>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="np-dash-content-row">
                    <section class="np-dash-panel np-dash-recent" aria-labelledby="np-recent-heading">
                        <div class="np-dash-panel-heading"><div><h2 id="np-recent-heading">Recent orders</h2><p>Realistic order statuses and payment activity</p></div>@if($canViewOrders)<a href="{{ route('admin.orders.index') }}" class="np-dash-more">View all <span>›</span></a>@endif</div>
                        <div class="np-dash-order-tabs" role="group" aria-label="Filter recent orders">
                            <button type="button" class="is-active" aria-pressed="true" data-orders-filter="all">All orders</button>
                            <button type="button" aria-pressed="false" data-orders-filter="paid">Paid</button>
                            <button type="button" aria-pressed="false" data-orders-filter="pending">Pending</button>
                            <button type="button" aria-pressed="false" data-orders-filter="processing">In production</button>
                            <button type="button" aria-pressed="false" data-orders-filter="delivered">Delivered</button>
                        </div>
                        <div class="np-dash-table-scroll"><table class="np-dash-table"><thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>
                            @forelse($recentOrders as $order)
                                <tr data-order-row data-order-status="{{ $order->status }}" data-order-payment="{{ $order->payment_status }}">
                                    <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                    <td><span class="np-dash-customer"><i>{{ strtoupper(substr(trim((string) $order->customer_name), 0, 2)) }}</i><span>{{ \Illuminate\Support\Str::limit((string) $order->customer_name, 20) }}</span></span></td>
                                    <td><b>{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</b></td>
                                    <td><x-admin.status-pill :status="$order->payment_status" /></td><td><x-admin.status-pill :status="$order->status" /></td>
                                </tr>
                            @empty<tr><td colspan="5" class="np-dash-empty">No orders in this period.</td></tr>@endforelse
                            </tbody></table></div>
                        <div class="np-dash-panel-footer"><span data-orders-label>Showing {{ $recentOrders->count() }} of {{ number_format($totalOrders) }} orders</span>@if($canViewOrders)<a href="{{ route('admin.orders.index') }}" aria-label="Open full orders list">‹ &nbsp; ›</a>@endif</div>
                    </section>
                    <section class="np-dash-panel np-dash-products" aria-labelledby="np-products-heading">
                        <div class="np-dash-panel-heading"><div><h2 id="np-products-heading">Top-selling products</h2><p>Highest demand this period</p></div>@if($canViewProducts)<a href="{{ route('admin.products.index') }}" class="np-dash-more">View all <span>›</span></a>@endif</div>
                        <div class="np-dash-product-list">
                            @forelse($topProducts as $product)
                                @php($actualProduct = $topProductRecords->get($product->product_id))
                                <div class="np-dash-product">
                                    <span class="np-dash-product-image">@if($actualProduct)<img loading="lazy" alt="" src="{{ $actualProduct->primaryImageUrl() }}">@else<x-admin.dashboard-icon name="package" :size="23" />@endif</span>
                                    <span class="np-dash-product-copy"><b>{{ $product->product_name }}</b><small>{{ $product->total_sold }} sold · {{ $product->sku ?: 'No SKU' }}</small></span>
                                    <span class="np-dash-product-price"><b>{{ $currency }} {{ number_format((float) $product->revenue, 2) }}</b><small>Sales</small></span>
                                </div>
                            @empty
                                <p class="np-dash-empty">No paid product sales in this period.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="np-dash-bottom-row">
                    <section class="np-dash-panel np-dash-pipeline" aria-labelledby="np-pipeline-heading"><div class="np-dash-panel-heading"><div><h2 id="np-pipeline-heading">Bulk quote pipeline</h2><p>From request to confirmed order</p></div>@if($canViewOrders)<a href="{{ route('admin.bulk-quotes.index') }}" class="np-dash-more">All quotes <span>›</span></a>@endif</div>
                        <div class="np-dash-pipeline-boxes">@foreach($quotePipeline as $label => $value)<div><i class="np-dash-bar np-dash-bar-{{ $loop->index }}"></i><strong>{{ number_format($value) }}</strong><span>{{ $label }}</span></div>@endforeach</div>
                        @if($quotePipeline['New requests'] > 0)<a class="np-dash-pipeline-attention" href="{{ route('admin.bulk-quotes.index') }}"><b>{{ $quotePipeline['New requests'] }} new requests</b><span>Review customer quote requests</span><em>Review now ›</em></a>@else<p class="np-dash-pipeline-attention">No new quote requests in this period.</p>@endif
                    </section>
                    <section class="np-dash-panel np-dash-fulfillment" aria-labelledby="np-fulfillment-heading"><div class="np-dash-panel-heading"><div><h2 id="np-fulfillment-heading">Fulfillment overview</h2><p>Production through delivery</p></div>@if($canViewOrders)<a href="{{ route('admin.orders.index') }}" class="np-dash-more">Manage <span>›</span></a>@endif</div>
                        <div class="np-dash-fulfillment-items">
                            @foreach([
                                ['Awaiting artwork approval', 'Customer proof decisions', $proofCount, 'palette', route('admin.artwork-approvals.index'), $canViewOrders],
                                ['Ready to dispatch', 'Shipments being prepared', $shipmentCount, 'truck', route('admin.orders.index'), $canViewOrders],
                                ['Returns requiring review', 'Open customer returns', $returnCount, 'return', route('admin.returns.index'), $canViewReturns],
                            ] as $fulfillment)
                                @if($fulfillment[5])<a href="{{ $fulfillment[4] }}" class="np-dash-fulfillment-item"><span><x-admin.dashboard-icon :name="$fulfillment[3]" :size="17" /></span><span><b>{{ $fulfillment[0] }}</b><small>{{ $fulfillment[1] }}</small></span><strong>{{ str_pad((string) $fulfillment[2], 2, '0', STR_PAD_LEFT) }}</strong></a>@endif
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <aside class="np-dash-rail" aria-label="Dashboard utilities">
                <section class="np-dash-panel np-dash-quick"><div class="np-dash-panel-heading"><div><h2>Quick actions</h2><p>Common admin tasks in one tap</p></div></div>
                    @if($canManageProducts)<a class="np-dash-quick-primary" href="{{ route('admin.products.create') }}"><x-admin.dashboard-icon name="plus" :size="14" /> Add new product</a>@endif
                    @if($canViewOrders)<a href="{{ route('admin.orders.index') }}"><x-admin.dashboard-icon name="cart" :size="14" /> Manage orders <span>›</span></a>@endif
                    @if(auth('admin')->user()?->canAdmin('coupons.manage'))<a href="{{ route('admin.coupons.create') }}"><x-admin.dashboard-icon name="tag" :size="14" /> Create discount <span>›</span></a>@endif
                    @if($canViewOrders)<a href="{{ route('admin.dashboard', ['days' => $days, 'export' => 'csv']) }}"><x-admin.dashboard-icon name="chart" :size="14" /> View reports <span>›</span></a>@endif
                </section>
                <section class="np-dash-panel np-dash-notifications"><div class="np-dash-panel-heading"><div><h2>Recent notifications</h2><p>Latest activity across your store</p></div><form action="{{ route('admin.notifications.read-all') }}" method="POST">@csrf<button type="submit" class="np-dash-more">Mark read</button></form></div>
                    <div class="np-dash-notification-list">@forelse($recentNotifications as $notification)
                        @php($message = (array) $notification->data)
                        <a href="{{ route('admin.notifications.index') }}"><span class="np-dash-notif-icon"><x-admin.dashboard-icon :name="$loop->index % 2 === 0 ? 'file' : 'bell'" :size="14" /></span><span><b>{{ (string) ($message['title'] ?? 'Store notification') }}</b><small>{{ (string) ($message['message'] ?? 'Store activity') }}</small></span><time>{{ $notification->created_at?->diffForHumans(short: true) }}</time>@unless($notification->read_at)<i class="np-dash-new"></i>@endunless</a>
                    @empty<p class="np-dash-empty">No notifications yet.</p>@endforelse</div>
                </section>
                <section class="np-dash-panel np-dash-health"><div class="np-dash-panel-heading"><div><h2>Store health</h2><p>Operations at a glance</p></div>@if($canViewOrders)<a href="{{ route('admin.dashboard', ['days' => $days, 'export' => 'csv']) }}" class="np-dash-more">Details</a>@endif</div>
                    @foreach([['Payment success', $health['payment'], 'orange'], ['On-time fulfillment', $health['fulfillment'], 'blue'], ['Quote response rate', $health['quotes'], 'green']] as $metric)
                        <div class="np-dash-health-row"><div><strong>{{ $metric[0] }}</strong><span>{{ $metric[1] === null ? 'N/A' : number_format($metric[1], 1).'%' }}</span></div><div class="np-dash-health-track"><span class="np-dash-health-{{ $metric[2] }}" style="width:{{ $metric[1] ?? 0 }}%"></span></div></div>
                    @endforeach
                </section>
                <section class="np-dash-panel np-dash-heads"><div class="np-dash-panel-heading"><div><h2>Heads up</h2><p>Work that could need attention</p></div></div>
                    @if($quotePipeline['Under review'] > 0)<a href="{{ route('admin.bulk-quotes.index') }}"><span><x-admin.dashboard-icon name="clock" :size="17" /></span><span><b>{{ $quotePipeline['Under review'] }} bulk quotes await response</b><small>Open the quote queue to follow up</small><em>Open queue ›</em></span></a>
                    @elseif($paymentDue > 0)<a href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}"><span><x-admin.dashboard-icon name="clock" :size="17" /></span><span><b>{{ $paymentDue }} orders need payment review</b><small>Check pending transactions</small><em>Open orders ›</em></span></a>
                    @else<div class="np-dash-heads-empty"><x-admin.dashboard-icon name="check" :size="18" /><span>All caught up with current priorities.</span></div>@endif
                </section>
            </aside>
        </div>
        <footer class="np-dash-copyright"><span>© {{ date('Y') }} NextPlay Sportswear · Interactive admin dashboard</span><span>Live store data · Your existing admin permissions apply</span></footer>
    </div>
