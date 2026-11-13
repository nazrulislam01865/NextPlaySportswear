<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkProof;
use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|StreamedResponse
    {
        $admin = auth('admin')->user();
        $canViewOrders = (bool) ($admin?->canAdmin('orders.view') ?? false);
        $canViewReturns = (bool) ($admin?->canAdmin('returns.view') ?? false);
        $canViewProducts = (bool) ($admin?->canAdmin('products.view') ?? false);
        $canManageProducts = (bool) ($admin?->canAdmin('products.manage') ?? false);
        $days = in_array((int) $request->query('days', 30), [7, 30, 90], true) ? (int) $request->query('days', 30) : 30;
        $start = CarbonImmutable::now()->startOfDay()->subDays($days - 1);

        $ordersAvailable = $canViewOrders && Schema::hasTable('orders');
        $bulkQuotesAvailable = $canViewOrders && Schema::hasTable('bulk_quote_requests');
        $returnsAvailable = $canViewReturns && Schema::hasTable('order_return_requests');
        $productsAvailable = $canViewProducts && Schema::hasTable('products');
        $notificationsAvailable = $admin && Schema::hasTable('notifications');
        $customersAvailable = $admin?->canAdmin('customers.view') && Schema::hasTable('users');

        // Existing limited result sets serve the cards and determine the display currency.
        $recentOrders = $ordersAvailable
            ? Order::query()->where('placed_at', '>=', $start)->latest('placed_at')->limit(5)->get()
            : collect();
        $currency = (string) ($recentOrders->firstWhere('payment_status', 'paid')?->currency ?? $recentOrders->first()?->currency ?? 'BDT');
        $recentNotifications = $notificationsAvailable
            ? $admin->notifications()->latest()->limit(4)->get()
            : collect();

        $orderStats = $ordersAvailable
            ? Order::query()->where('placed_at', '>=', $start)
                ->selectRaw("COUNT(*) AS total_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN payment_status IN ('pending', 'failed') THEN 1 ELSE 0 END) AS payment_due_count,
                    SUM(CASE WHEN delivered_at IS NOT NULL AND estimated_delivery_end_at IS NOT NULL THEN 1 ELSE 0 END) AS comparable_delivery_count,
                    SUM(CASE WHEN delivered_at IS NOT NULL AND estimated_delivery_end_at IS NOT NULL AND delivered_at <= estimated_delivery_end_at THEN 1 ELSE 0 END) AS on_time_count")
                ->first()
            : null;
        $totalOrders = (int) ($orderStats?->total_count ?? 0);

        // Revenue comes from the same daily aggregation used by the chart;
        // no extra SUM query or cross-currency addition is needed.
        $orderStatuses = $ordersAvailable
            ? Order::query()->where('placed_at', '>=', $start)
                ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all()
            : [];
        $statusGroups = [
            'Processing' => array_sum(array_map('intval', array_filter($orderStatuses, static fn ($key): bool => ! in_array($key, ['delivered', 'completed', 'shipped', 'partially_shipped', 'cancelled'], true), ARRAY_FILTER_USE_KEY))),
            'Delivered' => (int) ($orderStatuses['delivered'] ?? 0) + (int) ($orderStatuses['completed'] ?? 0),
            'Shipped' => (int) ($orderStatuses['shipped'] ?? 0) + (int) ($orderStatuses['partially_shipped'] ?? 0),
            'Cancelled' => (int) ($orderStatuses['cancelled'] ?? 0),
        ];
        $dailyOrders = $ordersAvailable
            ? Order::query()->where('placed_at', '>=', $start)
                ->selectRaw("DATE(placed_at) AS day, COUNT(*) AS orders_count,
                    SUM(CASE WHEN payment_status = 'paid' AND currency = ? THEN grand_total ELSE 0 END) AS revenue", [$currency])
                ->groupByRaw('DATE(placed_at)')->orderBy('day')->get()->keyBy('day')
            : collect();
        $dailyCustomers = $customersAvailable
            ? User::query()->where('role', 'customer')->where('created_at', '>=', $start)->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
                ->groupByRaw('DATE(created_at)')->orderBy('day')->pluck('total', 'day')
            : collect();
        $chart = [];
        for ($index = 0; $index < $days; $index++) {
            $day = $start->addDays($index)->toDateString();
            $chart[] = [
                'day' => $day,
                'revenue' => (float) ($dailyOrders->get($day)?->revenue ?? 0),
                'orders' => (int) ($dailyOrders->get($day)?->orders_count ?? 0),
                'customers' => (int) ($dailyCustomers->get($day) ?? 0),
            ];
        }
        $revenue = array_sum(array_column($chart, 'revenue'));
        if ($request->query('export') === 'csv') {
            abort_unless($canViewOrders, 403);
            return response()->streamDownload(static function () use ($chart, $currency): void {
                $handle = fopen('php://output', 'wb');
                fputcsv($handle, ['Date', 'Revenue ('.$currency.')', 'Orders', 'New customers']);
                foreach ($chart as $day) {
                    fputcsv($handle, [$day['day'], $day['revenue'], $day['orders'], $day['customers']]);
                }
                fclose($handle);
            }, 'nextplay-store-performance-'.$days.'-days.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $quoteGroups = $bulkQuotesAvailable
            ? BulkQuoteRequest::query()->where('created_at', '>=', $start)
                ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all()
            : [];
        $totalQuotes = array_sum(array_map('intval', $quoteGroups));
        $quotePipeline = [
            'New requests' => (int) ($quoteGroups[BulkQuoteRequest::STATUS_NEW] ?? 0),
            'Under review' => (int) ($quoteGroups[BulkQuoteRequest::STATUS_UNDER_REVIEW] ?? 0) + (int) ($quoteGroups[BulkQuoteRequest::STATUS_QUALIFIED] ?? 0) + (int) ($quoteGroups[BulkQuoteRequest::STATUS_QUOTATION_PREPARING] ?? 0),
            'Quote sent' => (int) ($quoteGroups[BulkQuoteRequest::STATUS_QUOTATION_SENT] ?? 0) + (int) ($quoteGroups[BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED] ?? 0),
            'Converted' => (int) ($quoteGroups[BulkQuoteRequest::STATUS_CONVERTED] ?? 0),
        ];
        $topProducts = $ordersAvailable && $productsAvailable && Schema::hasTable('order_items')
            ? OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNull('orders.deleted_at')->where('orders.payment_status', 'paid')
                ->where('orders.placed_at', '>=', $start)->where('orders.currency', $currency)
                ->selectRaw('order_items.product_id, order_items.product_name, order_items.sku,
                    SUM(order_items.quantity) AS total_sold, SUM(order_items.line_total) AS revenue')
                ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku')
                ->orderByDesc('revenue')->limit(4)->get()
            : collect();
        $topProductRecords = $topProducts->isNotEmpty()
            ? Product::query()->with('images')->whereIn('id', $topProducts->pluck('product_id')->filter()->all())->get()->keyBy('id')
            : collect();
        $proofCount = $canViewOrders && Schema::hasTable('artwork_proofs')
            ? ArtworkProof::query()->where('status', ArtworkProof::PENDING)->count() : 0;
        $shipmentCount = $ordersAvailable && Schema::hasTable('order_shipments')
            ? OrderShipment::query()->whereIn('status', ['preparing', 'label_created'])->count() : 0;
        $returnCount = $returnsAvailable
            ? OrderReturnRequest::query()->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count() : 0;
        $customerCount = $customersAvailable ? User::query()->where('role', 'customer')->count() : 0;
        $totalPaid = (int) ($orderStats?->paid_count ?? 0);
        $comparableDelivery = (int) ($orderStats?->comparable_delivery_count ?? 0);
        $respondedQuotes = (int) $quotePipeline['Quote sent'] + (int) $quotePipeline['Converted'] + (int) ($quoteGroups[BulkQuoteRequest::STATUS_ACCEPTED] ?? 0);

        // The reporting-period filter reuses the exact same aggregates, but sends
        // only dashboard markup. No second endpoint or additional database reads.
        $view = $request->header('X-NextPlay-Dashboard') === 'partial'
            ? 'admin.partials.dashboard-content'
            : 'admin.dashboard';

        return view($view, [
            'days' => $days, 'currency' => $currency, 'revenue' => $revenue, 'chart' => $chart,
            'totalOrders' => $totalOrders, 'customerCount' => $customerCount, 'totalQuotes' => $totalQuotes,
            'statusGroups' => $statusGroups, 'quotePipeline' => $quotePipeline, 'topProducts' => $topProducts,
            'topProductRecords' => $topProductRecords,
            'recentOrders' => $recentOrders,
            'recentNotifications' => $recentNotifications,
            'proofCount' => $proofCount, 'shipmentCount' => $shipmentCount, 'returnCount' => $returnCount,
            'paymentDue' => (int) ($orderStats?->payment_due_count ?? 0),
            'health' => [
                'payment' => $totalOrders > 0 ? round($totalPaid * 100 / $totalOrders, 1) : null,
                'fulfillment' => $comparableDelivery > 0 ? round((int) ($orderStats?->on_time_count ?? 0) * 100 / $comparableDelivery, 1) : null,
                'quotes' => $totalQuotes > 0 ? round($respondedQuotes * 100 / $totalQuotes, 1) : null,
            ],
            'canViewOrders' => $canViewOrders, 'canViewReturns' => $canViewReturns,
            'canViewProducts' => $canViewProducts, 'canManageProducts' => $canManageProducts,
        ]);
    }
}
