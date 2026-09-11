<?php

namespace App\Services\Integrations\FlowTrack;

use App\Exceptions\Integrations\FlowTrackIntegrationException;
use App\Models\BulkQuoteRequest;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class FlowTrackClient
{
    public function __construct(
        private readonly FlowTrackOrderPayloadFactory $orderPayloadFactory,
        private readonly FlowTrackInquiryPayloadFactory $inquiryPayloadFactory,
    ) {
    }

    /** @return array<string, mixed> */
    public function health(): array
    {
        return $this->healthAt('health', 'FlowTrack order health check');
    }

    /** @return array<string, mixed> */
    public function inquiryHealth(): array
    {
        return $this->healthAt('inquiry_health', 'FlowTrack Inquiry health check');
    }

    /** @return array<string, mixed> */
    public function sendOrder(Order $order): array
    {
        $this->assertConfigured();

        $freshOrder = Order::query()
            ->withTrashed()
            ->with('items')
            ->findOrFail($order->id);

        $payload = $this->orderPayloadFactory->make($freshOrder);
        $idempotencyKey = trim((string) ($freshOrder->idempotency_key ?: 'nextplay-order-'.$freshOrder->id));

        try {
            $response = $this->request()
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post($this->endpoint('orders'), $payload);
        } catch (ConnectionException $exception) {
            throw new FlowTrackIntegrationException(
                'Could not connect to FlowTrack while sending order '.$freshOrder->order_number.'.',
                previous: $exception,
            );
        }

        return $this->validatedJsonResponse($response, 'FlowTrack order receiver');
    }

    /** @return array<string, mixed> */
    public function sendInquiry(BulkQuoteRequest $quote): array
    {
        $this->assertConfigured();

        $freshQuote = BulkQuoteRequest::query()
            ->with('user')
            ->findOrFail($quote->id);

        $payload = $this->inquiryPayloadFactory->make($freshQuote);
        $idempotencyKey = $this->inquiryPayloadFactory->idempotencyKey($freshQuote);

        try {
            $response = $this->request()
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post($this->endpoint('inquiries'), $payload);
        } catch (ConnectionException $exception) {
            throw new FlowTrackIntegrationException(
                'Could not connect to FlowTrack while sending bulk quote '.$freshQuote->reference.'.',
                previous: $exception,
            );
        }

        return $this->validatedJsonResponse($response, 'FlowTrack Inquiry receiver');
    }

    /** @return array<string, mixed> */
    private function healthAt(string $endpoint, string $context): array
    {
        $this->assertConfigured();

        try {
            $response = $this->request()->get($this->endpoint($endpoint));
        } catch (ConnectionException $exception) {
            throw new FlowTrackIntegrationException(
                'Could not connect to FlowTrack at '.config('flowtrack.base_url').'.',
                previous: $exception,
            );
        }

        return $this->validatedJsonResponse($response, $context);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken(trim((string) config('flowtrack.token')))
            ->connectTimeout((int) config('flowtrack.http.connect_timeout', 2))
            ->timeout((int) config('flowtrack.http.timeout', 10));
    }

    private function endpoint(string $name): string
    {
        $path = trim((string) config("flowtrack.endpoints.$name"));
        if ($path === '') {
            throw new FlowTrackIntegrationException('FlowTrack endpoint ['.$name.'] is not configured.');
        }

        return rtrim((string) config('flowtrack.base_url'), '/')
            .'/'.ltrim($path, '/');
    }

    private function assertConfigured(): void
    {
        if (! (bool) config('flowtrack.enabled')) {
            throw new FlowTrackIntegrationException('FlowTrack integration is disabled in NextPlay.');
        }

        if (trim((string) config('flowtrack.base_url')) === '') {
            throw new FlowTrackIntegrationException('FLOWTRACK_BASE_URL is not configured.');
        }

        if (trim((string) config('flowtrack.token')) === '') {
            throw new FlowTrackIntegrationException('FLOWTRACK_INTEGRATION_TOKEN is not configured.');
        }
    }

    /** @return array<string, mixed> */
    private function validatedJsonResponse(Response $response, string $context): array
    {
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ($json['ok'] ?? false) !== true) {
            $message = is_array($json) && filled($json['message'] ?? null)
                ? (string) $json['message']
                : $response->body();

            throw new FlowTrackIntegrationException(sprintf(
                '%s failed with HTTP %d: %s',
                $context,
                $response->status(),
                mb_substr(trim($message) ?: 'Unexpected response from FlowTrack.', 0, 1500),
            ));
        }

        return $json;
    }
}
