<?php

namespace App\Http\Resources\Api\V1\Cart;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class CartResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $cart = (array) $this->resource;
        $couponCode = filled($cart['coupon_code'] ?? null) ? (string) $cart['coupon_code'] : null;

        return [
            'items' => collect((array) ($cart['items'] ?? []))
                ->map(fn (array $item): array => (new CartItemResource($item))->resolve($request))
                ->values()
                ->all(),
            'quantity' => (int) ($cart['quantity'] ?? 0),
            'is_empty' => (bool) ($cart['is_empty'] ?? true),
            'checkout_ready' => (bool) ($cart['checkout_ready'] ?? false),
            'coupon' => $couponCode !== null ? [
                'code' => $couponCode,
                'message' => $cart['coupon_message'] ?? null,
            ] : null,
            'coupon_error' => $cart['coupon_error'] ?? null,
            'subtotal' => round((float) ($cart['subtotal'] ?? 0), 2),
            'customization_total' => round((float) ($cart['customization_total'] ?? 0), 2),
            'merchandise_total' => round((float) ($cart['merchandise_total'] ?? 0), 2),
            'discount' => round((float) ($cart['discount'] ?? 0), 2),
            'shipping' => round((float) ($cart['shipping'] ?? 0), 2),
            'additional_shipping' => round((float) ($cart['additional_shipping'] ?? 0), 2),
            'product_shipping_total' => round((float) ($cart['product_shipping_total'] ?? 0), 2),
            'tax' => round((float) ($cart['tax'] ?? 0), 2),
            'total' => round((float) ($cart['total'] ?? 0), 2),
            'currency' => 'USD',
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => ['authoritative' => true, 'contract' => 'cart-v1'],
            'request_id' => RequestId::from($request),
        ];
    }
}
