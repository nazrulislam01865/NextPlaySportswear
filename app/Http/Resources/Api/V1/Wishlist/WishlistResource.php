<?php

namespace App\Http\Resources\Api\V1\Wishlist;

use App\Http\Resources\Api\V1\ApiResource;
use App\Http\Resources\Api\V1\Catalog\ProductCardResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class WishlistResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'items' => collect((array) ($this->resource['items'] ?? []))
                ->map(fn (array $product): array => (new ProductCardResource($product))->resolve($request))
                ->values()
                ->all(),
            'product_ids' => array_values((array) ($this->resource['product_ids'] ?? [])),
            'count' => (int) ($this->resource['count'] ?? 0),
            'authenticated' => (bool) ($this->resource['authenticated'] ?? false),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => ['contract' => 'wishlist-v1'],
            'request_id' => RequestId::from($request),
        ];
    }
}
