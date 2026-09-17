<?php

namespace App\Http\Resources\Api\V1\Checkout;

use App\Http\Resources\Api\V1\ApiResource;
use App\Http\Resources\Api\V1\Cart\CartItemResource;
use App\Http\Resources\Api\V1\Cart\CartResource;
use Illuminate\Http\Request;

final class CheckoutResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;
        $rawSummary = (array) ($data['summary'] ?? []);
        $rawSummaryItems = (array) ($rawSummary['items'] ?? []);
        unset($rawSummary['items']);
        $summary = $this->sanitize($rawSummary);
        $summary['items'] = collect($rawSummaryItems)
            ->map(fn (array $item): array => (new CartItemResource($item))->resolve($request))
            ->values()->all();

        return [
            'cart' => (new CartResource((array) ($data['cart'] ?? [])))->resolve($request),
            'state' => $this->sanitizeState((array) ($data['state'] ?? [])),
            'steps' => array_values((array) ($data['steps'] ?? [])),
            'first_incomplete_step' => $data['first_incomplete_step'] ?? null,
            'current_step' => (string) ($data['current_step'] ?? 'information'),
            'can_review' => (bool) ($data['can_review'] ?? false),
            'can_place_order' => (bool) ($data['can_place_order'] ?? false),
            'saved_contact' => $this->sanitize((array) ($data['saved_contact'] ?? [])),
            'saved_shipping_addresses' => array_values((array) ($data['saved_shipping_addresses'] ?? [])),
            'saved_billing_addresses' => array_values((array) ($data['saved_billing_addresses'] ?? [])),
            'saved_payment_methods' => array_values((array) ($data['saved_payment_methods'] ?? [])),
            'saved_card_gateway' => $this->sanitize($data['saved_card_gateway'] ?? null),
            'payment_options' => $this->sanitize(array_values((array) ($data['payment_options'] ?? []))),
            'summary' => $summary,
            'order_idempotency_key' => (string) ($data['order_idempotency_key'] ?? ''),
        ];
    }

    private function sanitizeState(array $state): array
    {
        return $this->sanitize([
            'information' => $state['information'] ?? null,
            'shipping_address' => $state['shipping_address'] ?? null,
            'billing_address' => $state['billing_address'] ?? null,
            'payment_method' => $state['payment_method'] ?? null,
            'review' => $state['review'] ?? null,
        ]);
    }

    private function sanitize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $blocked = ['password', 'remember_token', 'provider_reference', 'card_number', 'cvv', 'cvc', 'secret', 'token', 'path', 'artwork_path'];
        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), $blocked, true)) {
                continue;
            }
            $result[$key] = $this->sanitize($item);
        }

        return $result;
    }
}
