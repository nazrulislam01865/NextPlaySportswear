<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));

        if ($sessionId === '') {
            return redirect()->route('order.confirmation')
                ->with('status', 'Your payment is being verified securely.');
        }

        $payment = OrderPayment::query()
            ->with('order')
            ->where('provider', $provider)
            ->where('provider_session_id', $sessionId)
            ->first();

        if ($payment?->order && $request->user()?->id === $payment->order->user_id) {
            $message = $payment->status === 'paid'
                ? 'Payment confirmed successfully.'
                : 'Stripe returned successfully. Payment status is being verified from the signed provider webhook.';

            return redirect()->route('account.orders.show', $payment->order)->with('status', $message);
        }

        return redirect()->route('order.confirmation')
            ->with('status', 'Payment return received. The order will update only after provider verification.');
    }
}
