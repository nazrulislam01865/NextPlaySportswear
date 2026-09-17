<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

final class VerificationStatusResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'email' => (string) $this->resource->email,
            'email_verified' => $this->resource->hasVerifiedEmail(),
            'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
        ];
    }
}
