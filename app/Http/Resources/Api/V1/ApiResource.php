<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\RequestId;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => (object) [],
            'request_id' => RequestId::from($request),
        ];
    }
}
