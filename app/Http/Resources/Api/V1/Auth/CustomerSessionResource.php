<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class CustomerSessionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $customer = is_array($this->resource)
            ? $this->resource['customer']
            : $this->resource;

        return [
            'customer' => (new CustomerResource($customer))->resolve($request),
        ];
    }

    public function with(Request $request): array
    {
        $customer = is_array($this->resource)
            ? $this->resource['customer']
            : $this->resource;

        return [
            'meta' => [
                'authenticated' => true,
                'verification_required' => ! $customer->hasVerifiedEmail(),
                'verification_delivery' => is_array($this->resource)
                    ? ($this->resource['verification_delivery'] ?? null)
                    : null,
            ],
            'request_id' => RequestId::from($request),
        ];
    }
}
