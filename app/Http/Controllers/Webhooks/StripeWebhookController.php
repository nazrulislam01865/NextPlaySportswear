<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\PaymentGatewayManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $gateways): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $gateways->gateway('stripe')->verifyWebhook($payload, $signature);
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return response()->json(['received' => false], 400);
        }

        try {
            $webhook = PaymentWebhookEvent::query()->firstOrCreate(
                ['provider' => 'stripe', 'provider_event_id' => $event->providerEventId],
                [
                    'event_type' => $event->type,
                    'payload_hash' => hash('sha256', $payload),
                    'payload' => $event->payload,
                    'status' => 'received',
                    'received_at' => now(),
                ],
            );
        } catch (QueryException) {
            $webhook = PaymentWebhookEvent::query()
                ->where('provider', 'stripe')
                ->where('provider_event_id', $event->providerEventId)
                ->firstOrFail();
        }

        if ($webhook->status !== 'processed') {
            ProcessPaymentWebhook::dispatch($webhook->id)
                ->onQueue((string) config('payments.webhooks.queue', 'payments'));
        }

        return response()->json(['received' => true]);
    }
}
