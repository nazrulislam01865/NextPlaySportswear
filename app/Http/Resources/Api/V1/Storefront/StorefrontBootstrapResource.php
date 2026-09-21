<?php

namespace App\Http\Resources\Api\V1\Storefront;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

final class StorefrontBootstrapResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'site' => (array) ($this->resource['site'] ?? []),
            'customer' => $this->resource['customer'] ?? null,
            'cart' => (array) ($this->resource['cart'] ?? []),
            'wishlist' => (array) ($this->resource['wishlist'] ?? []),
            'navigation' => (array) ($this->resource['navigation'] ?? []),
            'header' => (array) ($this->resource['header'] ?? []),
            'footer' => (array) ($this->resource['footer'] ?? []),
        ];
    }

}