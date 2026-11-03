<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Payments\PaymentGatewayManager;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::query()->orderBy('sort_order')->latest()->paginate($this->adminPerPage(20))->withQueryString(),
            'gatewayStatuses' => $this->gateways->statuses(),
        ]);
    }

    // Retain the route names for compatibility, but deny all administrator
    // configuration writes and editing screens including super-admin access.
    public function create(): never
    {
        abort(403, 'Payment method configuration is read-only for NextPlay administrators.');
    }

    public function store(): never
    {
        abort(403, 'Payment method configuration is read-only for NextPlay administrators.');
    }

    public function edit(PaymentMethod $paymentMethod): never
    {
        abort(403, 'Payment method configuration is read-only for NextPlay administrators.');
    }

    public function update(PaymentMethod $paymentMethod): never
    {
        abort(403, 'Payment method configuration is read-only for NextPlay administrators.');
    }

    public function destroy(PaymentMethod $paymentMethod): never
    {
        abort(403, 'Payment method configuration is read-only for NextPlay administrators.');
    }
}
