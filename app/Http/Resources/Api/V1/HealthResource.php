<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

final class HealthResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'status' => (string) ($this->resource['status'] ?? 'ok'),
            'api_version' => (string) ($this->resource['api_version'] ?? 'v1'),
            'timestamp' => (string) ($this->resource['timestamp'] ?? now()->toIso8601String()),
        ];
    }
}
