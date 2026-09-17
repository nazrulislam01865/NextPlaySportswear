<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\ReviewConfirmationRequest;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutApiGuard;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;

final class CheckoutReviewController extends Controller
{
    public function show(Request $request, CheckoutService $checkout, CheckoutApiGuard $guard): CheckoutResource
    {
        $guard->requireStep($checkout, 'review');
        return new CheckoutResource($checkout->stateData($request->user('web')));
    }

    public function update(ReviewConfirmationRequest $request, CheckoutService $checkout, CheckoutApiGuard $guard): CheckoutResource
    {
        $guard->requireStep($checkout, 'review');
        $checkout->confirmReview($request->validated());
        return new CheckoutResource($checkout->stateData($request->user('web')));
    }
}
