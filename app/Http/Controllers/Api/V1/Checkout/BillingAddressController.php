<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\BillingAddressRequest;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutApiGuard;
use App\Services\Checkout\CheckoutMutationIdempotencyService;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;

final class BillingAddressController extends Controller
{
    public function __invoke(
        BillingAddressRequest $request,
        CheckoutService $checkout,
        CheckoutApiGuard $guard,
        CheckoutMutationIdempotencyService $idempotency,
    ): CheckoutResource|JsonResponse {
        $guard->requireStep($checkout, 'billing');
        $payload = $request->validated();
        $replayed = $idempotency->replay($request, 'checkout.billing-address', $payload);

        if ($replayed !== null) {
            return (new CheckoutResource($replayed))->response()->header('Idempotency-Replayed', 'true');
        }

        $checkout->storeBillingAddress($payload, $request->user('web'));
        $result = $checkout->stateData($request->user('web'));
        $idempotency->remember($request, 'checkout.billing-address', $payload, $result);

        return new CheckoutResource($result);
    }
}
