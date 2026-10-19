<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentOrchestrator $payments): RedirectResponse|View
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
            // A redirect proves nothing about payment success. For this authenticated
            // customer's own Stripe session, ask Stripe directly; webhooks remain primary.
            if ($provider === 'stripe' && in_array($payment->status, ['pending', 'processing'], true)) {
                try {
                    $payments->reconcile($payment);
                } catch (PaymentGatewayException $exception) {
                    // Network/provider problems must not produce a false paid state.
                    report($exception);
                }
            }

            $message = $payment->order->fresh()->payment_status === 'paid'
                ? 'Payment confirmed successfully.'
                : 'Your payment is being verified with the provider. The order will update once payment is confirmed.';

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
