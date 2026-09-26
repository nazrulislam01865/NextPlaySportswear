<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse|View
    {
        $sessionId = trim((string) $request->query('session_id', ''));

        if ($sessionId === '') {
            return $this->verificationView($provider, 'Your payment return was received. Provider confirmation is still being verified securely.');
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

        return $this->verificationView($provider, 'Payment return received. The order will update only after signed provider verification.');
    }

    private function verificationView(string $provider, string $message): View
    {
        return view('storefront.payments.verifying', [
            'provider' => ucfirst($provider),
            'message' => $message,
            'seo' => [
                'title' => 'Payment Verification | NextPlay Sportswear',
                'description' => 'Secure payment verification status.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }
}
