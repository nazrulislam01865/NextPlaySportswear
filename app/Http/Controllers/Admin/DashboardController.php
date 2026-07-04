<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSlide;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\Product;
use App\Models\User;
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
        $canViewInventory = $admin?->canAdmin('inventory.view') ?? false;
        $canViewCustomers = $admin?->canAdmin('customers.view') ?? false;

        $ordersAvailable = $canViewOrders && Schema::hasTable('orders');
        $returnsAvailable = $canViewReturns && Schema::hasTable('order_return_requests');

        $stats = [
            'orders' => $ordersAvailable ? Order::query()->count() : 0,
            'open_orders' => $ordersAvailable ? Order::query()->whereNotIn('status', ['completed', 'cancelled'])->count() : 0,
            'payment_due' => $ordersAvailable ? Order::query()->whereIn('payment_status', ['pending', 'failed'])->count() : 0,
            'open_returns' => $returnsAvailable ? OrderReturnRequest::query()->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count() : 0,
            'products' => Product::query()->count(),
            'active_products' => Product::query()->where('status', 'active')->where('is_active', true)->count(),
            'low_stock_products' => Product::query()->where('track_inventory', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'categories' => Category::query()->whereNull('parent_id')->count(),
            'active_slides' => Schema::hasTable('homepage_slides')
                ? HomepageSlide::query()->where('is_active', true)->count()
                : 0,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'canViewOrders' => $canViewOrders,
            'canManageOrders' => $canManageOrders,
            'canViewReturns' => $canViewReturns,
            'canViewProducts' => $canViewProducts,
            'canManageProducts' => $canManageProducts,
            'canViewInventory' => $canViewInventory,
            'canViewCustomers' => $canViewCustomers,
            'recentOrders' => $ordersAvailable
                ? Order::query()->with('user')->latest('placed_at')->limit(8)->get()
                : collect(),
            'recentProducts' => $canViewProducts
                ? Product::query()->with(['category', 'subcategory'])->latest()->limit(8)->get()
                : collect(),
        ]);
    }
}
