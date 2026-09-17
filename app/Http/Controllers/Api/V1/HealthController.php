<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HealthResource;

final class HealthController extends Controller
{
    public function __invoke(): HealthResource
    {
        return new HealthResource([
            'status' => 'ok',
            'api_version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
