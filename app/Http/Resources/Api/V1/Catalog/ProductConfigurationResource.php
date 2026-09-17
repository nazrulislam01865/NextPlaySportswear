<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class ProductConfigurationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }

    public function with(Request $request): array
    {
        return [
            'meta' => ['contract' => 'product-configuration-v1'],
            'request_id' => RequestId::from($request),
        ];
    }
}
