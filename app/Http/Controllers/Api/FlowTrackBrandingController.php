<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\FlowTrackBrandingSnapshot;
use Illuminate\Http\JsonResponse;

final class FlowTrackBrandingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['branding' => FlowTrackBrandingSnapshot::current()]);
    }
}
