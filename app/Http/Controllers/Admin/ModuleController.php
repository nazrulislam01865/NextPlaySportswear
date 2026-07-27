<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ModuleController extends Controller
{
    public function show(string $module): RedirectResponse
    {
        $route = match ($module) {
            'orders' => 'admin.orders.index',
            'inventory' => 'admin.products.index',
            'discounts' => 'admin.coupons.index',
            'content' => 'admin.homepage.sections.index',
            'shipping' => 'admin.shipping-methods.index',
            'payments' => 'admin.payment-methods.index',
            default => null,
        };

        abort_if($route === null, 404);

        return redirect()->route($route);
    }
}
