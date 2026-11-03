<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\PaymentGatewayManager;
use App\Services\Payments\SafeStripeWebhookPayload;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $gateways): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        // Keep oversized/untrusted payloads out of memory, storage and logs.
        if (strlen($payload) > max(1024, (int) config('payments.webhooks.max_payload_bytes', 262144))) {
            return response()->json(['received' => false], 413);
        }

        try {
            $event = $gateways->gateway('stripe')->verifyWebhook($payload, $signature);
        } catch (PaymentGatewayException $exception) {
            // Do not report the raw Stripe payload or signature.
            // Invalid signatures are untrusted traffic; do not log submitted values.
            return response()->json(['received' => false], 400);
        }

        if (! preg_match('/^evt_[a-zA-Z0-9_]{3,180}$/D', $event->providerEventId)
            || ! preg_match('/^[a-z_.]{3,100}$/D', $event->type)
            || filled($event->payload['account'] ?? null)) {
            Log::warning('Payment webhook identity or account rejected.');
            return response()->json(['received' => false], 400);
        }

        $hash = hash('sha256', $payload);
        try {
            $webhook = PaymentWebhookEvent::query()->firstOrCreate(
                ['provider' => 'stripe', 'provider_event_id' => $event->providerEventId],
                [
                    'event_type' => $event->type,
                    'payload_hash' => $hash,
                    'payload' => SafeStripeWebhookPayload::fromVerified($event->payload),
                    'status' => 'received',
                    'received_at' => now(),
                ],
            );
        } catch (QueryException) {
            // Concurrent Stripe delivery can hit the unique provider event ID.
            $webhook = PaymentWebhookEvent::query()->where('provider', 'stripe')
                ->where('provider_event_id', $event->providerEventId)->first();
            if (! $webhook) {
                return response()->json(['received' => false], 503);
            }
        }

        // One provider event ID must always mean one exact signed payload.
        if ($webhook->payload_hash !== $hash || $webhook->event_type !== $event->type) {
            Log::error('Conflicting signed Stripe webhook identity.', ['event_id' => $webhook->id]);
            return response()->json(['received' => false], 409);
        }

        if ($webhook->status !== 'processed') {
            try {
                ProcessPaymentWebhook::dispatch($webhook->id)
                    ->onQueue((string) config('payments.webhooks.queue', 'payments'));
                $webhook->forceFill(['last_dispatched_at' => now()])->save();
            } catch (Throwable $exception) {
                Log::error('Payment webhook enqueue failed.', [
                    'event_id' => $webhook->id, 'type' => class_basename($exception),
                ]);
                // A 503 asks Stripe to redeliver rather than silently losing a financial event.
                return response()->json(['received' => false], 503);
            }
        }

        return response()->json(['received' => true]);
    }
}
