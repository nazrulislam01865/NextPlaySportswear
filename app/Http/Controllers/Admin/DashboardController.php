<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSlide;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'products' => Product::query()->count(),
            'active_products' => Product::query()->where('status', 'active')->where('is_active', true)->count(),
            'featured_products' => Product::query()->where('is_featured', true)->count(),
            'low_stock_products' => Product::query()->where('track_inventory', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'categories' => Category::query()->whereNull('parent_id')->count(),
            'subcategories' => Category::query()->whereNotNull('parent_id')->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'active_slides' => Schema::hasTable('homepage_slides')
                ? HomepageSlide::query()->where('is_active', true)->count()
                : 0,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentProducts' => Product::query()->with(['category', 'subcategory'])->latest()->limit(8)->get(),
        ]);
    }
}
