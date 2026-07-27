<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $admin = auth('admin')->user();
        $canViewOrders = $admin?->canAdmin('orders.view') ?? false;
        $canManageOrders = $admin?->canAdmin('orders.manage') ?? false;
        $canViewReturns = $admin?->canAdmin('returns.view') ?? false;
        $canViewProducts = $admin?->canAdmin('products.view') ?? false;
        $canManageProducts = $admin?->canAdmin('products.manage') ?? false;

        $ordersAvailable = $canViewOrders && Schema::hasTable('orders');
        $returnsAvailable = $canViewReturns && Schema::hasTable('order_return_requests');
        $notificationsAvailable = $admin && Schema::hasTable('notifications');
        $productsAvailable = $canViewProducts && Schema::hasTable('products');

        $stats = [
            'orders' => $ordersAvailable ? Order::query()->count() : 0,
            'open_orders' => $ordersAvailable ? Order::query()->whereNotIn('status', ['completed', 'cancelled'])->count() : 0,
            'payment_due' => $ordersAvailable ? Order::query()->whereIn('payment_status', ['pending', 'failed'])->count() : 0,
            'open_returns' => $returnsAvailable ? OrderReturnRequest::query()->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count() : 0,
            'products' => $productsAvailable ? Product::query()->count() : 0,
            'active_products' => $productsAvailable
                ? Product::query()->where('status', 'active')->where('is_active', true)->count()
                : 0,
            'unread_notifications' => $notificationsAvailable ? $admin->unreadNotifications()->count() : 0,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'canViewOrders' => $canViewOrders,
            'canManageOrders' => $canManageOrders,
            'canViewReturns' => $canViewReturns,
            'canViewProducts' => $canViewProducts,
            'canManageProducts' => $canManageProducts,
            'recentNotifications' => $notificationsAvailable
                ? $admin->notifications()->latest()->paginate(5, ['*'], 'notifications_page')->withQueryString()
                : collect(),
            'recentOrders' => $ordersAvailable
                ? Order::query()->with('user')->latest('placed_at')->paginate(5, ['*'], 'orders_page')->withQueryString()
                : collect(),
            'recentProducts' => $productsAvailable
                ? Product::query()->with(['category', 'subcategory'])->latest()->paginate(5, ['*'], 'products_page')->withQueryString()
                : collect(),
        ]);
    }
}
