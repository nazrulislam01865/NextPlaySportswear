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

            $payment->refresh();
            $orderPaymentStatus = (string) $payment->order->fresh()->payment_status;
            $message = match (true) {
                $orderPaymentStatus === 'paid' => 'Payment confirmed successfully.',
                in_array($payment->status, ['failed', 'expired'], true) => 'Payment was unsuccessful or the session expired. You may retry from your order when eligible.',
                $payment->failure_code === 'attempt_declined' => 'The payment method was declined. You can retry securely from your order; the session may still be active.',
                $orderPaymentStatus === 'partially_paid' => 'A partial payment has been recorded. Any remaining payment must follow agreed payment terms.',
                default => 'Your payment is being verified with the provider. The order will update once payment is confirmed.',
            };

            if ($orderPaymentStatus === 'paid') {
                return redirect()->route('order.confirmation', ['order' => $payment->order->order_number])->with('status', $message);
            }

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
