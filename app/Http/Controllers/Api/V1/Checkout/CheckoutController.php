<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutApiGuard;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __invoke(Request $request, CheckoutService $checkout, CheckoutApiGuard $guard): CheckoutResource
    {
        $guard->requireCart($checkout);
        return new CheckoutResource($checkout->stateData($request->user('web')));
    }
}
