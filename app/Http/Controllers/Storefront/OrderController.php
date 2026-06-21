<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Order\TrackOrderRequest;
use App\Services\Order\OrderExperienceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderExperienceService $orders)
    {
    }

    public function confirmation(Request $request): View
    {
        $order = $this->orders->orderForNumber(null, allowDemo: true);

        return $this->view('storefront.orders.confirmation', $order, [
            'title' => 'Order Confirmation | NextPlay Sportswear',
            'description' => 'Order confirmation and next steps for your custom sportswear order.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function paymentSuccess(Request $request): View
    {
        $order = $this->orders->orderForNumber(null, allowDemo: true);

        return $this->view('storefront.orders.payment-success', $order, [
            'title' => 'Payment Success | NextPlay Sportswear',
            'description' => 'Secure payment return page for custom sportswear checkout.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function paymentFailed(Request $request): View
    {
        $order = $this->orders->orderForNumber(null, allowDemo: true);

        return $this->view('storefront.orders.payment-failed', $order, [
            'title' => 'Payment Failed | NextPlay Sportswear',
            'description' => 'Payment issue recovery page for custom sportswear checkout.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function details(?string $orderNumber = null): View|RedirectResponse
    {
        $order = $this->orders->orderForNumber($orderNumber, allowDemo: $orderNumber === null);

        if (! is_array($order)) {
            return redirect()->route('orders.track')->withErrors([
                'order_number' => 'Please verify your order number and email before viewing order details.',
            ]);
        }

        return $this->view('storefront.orders.details', $order, [
            'title' => 'Order Details | NextPlay Sportswear',
            'description' => 'Detailed order page for custom sportswear order information, production status, and invoice access.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function tracking(Request $request): View
    {
        $order = $this->orders->trackedOrder() ?? $this->orders->orderForNumber(null, allowDemo: true);

        return $this->view('storefront.orders.tracking', $order, [
            'title' => 'Track Order | NextPlay Sportswear',
            'description' => 'Track custom sportswear production, design review, shipping, and delivery status.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function lookup(TrackOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->lookupForTracking($request->validated());

        if (! is_array($order)) {
            return back()
                ->withInput($request->safe()->only(['order_number', 'email']))
                ->withErrors(['order_number' => 'We could not verify that order. Check the order number and email, then try again.']);
        }

        return redirect()->route('orders.track')->with('status', 'Order verified securely.');
    }

    public function invoice(?string $orderNumber = null): View|RedirectResponse
    {
        $order = $this->orders->orderForNumber($orderNumber, allowDemo: $orderNumber === null);

        if (! is_array($order)) {
            return redirect()->route('orders.track')->withErrors([
                'order_number' => 'Please verify your order before viewing an invoice.',
            ]);
        }

        return $this->view('storefront.orders.invoice', $order, [
            'title' => 'Invoice | NextPlay Sportswear',
            'description' => 'Invoice preview and print page for custom sportswear orders.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    private function view(string $view, array $order, array $seo): View
    {
        return view($view, array_merge($this->orders->pageData($order), [
            'seo' => $seo,
        ]));
    }
}
