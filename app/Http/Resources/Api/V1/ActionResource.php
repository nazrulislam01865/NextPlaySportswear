<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

final class ActionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'message' => (string) ($this->resource['message'] ?? ''),
            'result' => $this->resource['result'] ?? null,
        ];
    }
}
