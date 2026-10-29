<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\AddressGeography;
use Illuminate\Http\JsonResponse;

class AddressRegionsController extends Controller
{
    public function __invoke(string $iso, AddressGeography $geography): JsonResponse
    {
        abort_unless(preg_match('/^[A-Za-z]{2}$/', $iso), 404);

        return response()->json($geography->regions($iso)->map(fn ($region) => [
            'name' => $region->name,
            'code' => $region->code,
        ])->values())->header('Cache-Control', 'no-store');
    }
}
