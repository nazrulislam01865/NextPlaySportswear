<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function show(string $module): View
    {
        $modules = [
            'orders' => ['Orders', 'Manage orders, payment status, fulfillment, refunds, returns, invoices, and order notes.'],
            'customers' => ['Customers', 'Manage customer profiles, addresses, account status, order history, and customer groups.'],
            'inventory' => ['Inventory', 'Track stock, low-stock alerts, backorders, adjustments, and product-level availability.'],
            'discounts' => ['Discounts & Coupons', 'Create coupons, automatic discounts, usage limits, customer restrictions, and schedules.'],
            'reviews' => ['Reviews', 'Moderate verified reviews, ratings, media, replies, and publication status.'],
            'content' => ['Content', 'Manage homepage sections, menus, banners, static pages, FAQs, and reusable content blocks.'],
            'reports' => ['Reports', 'View sales, products, customers, inventory, tax, refunds, and conversion reports.'],
            'shipping' => ['Shipping', 'Manage shipping zones, methods, rates, delivery windows, and free-shipping rules.'],
            'taxes' => ['Taxes', 'Manage tax classes, jurisdictions, exemptions, and product tax mappings.'],
            'payments' => ['Payments', 'Configure payment methods, transaction status, refunds, and fraud-review settings.'],
            'settings' => ['Store Settings', 'Manage store identity, currency, checkout, emails, legal pages, security, and integrations.'],
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('admin.modules.show', [
            'module' => $module,
            'title' => $modules[$module][0],
            'description' => $modules[$module][1],
        ]);
    }
}
