<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\ShippingAddressRequest;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutApiGuard;
use App\Services\Checkout\CheckoutMutationIdempotencyService;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;

final class ShippingAddressController extends Controller
{
    public function __invoke(
        ShippingAddressRequest $request,
        CheckoutService $checkout,
        CheckoutApiGuard $guard,
        CheckoutMutationIdempotencyService $idempotency,
    ): CheckoutResource|JsonResponse {
        $guard->requireStep($checkout, 'shipping');
        $payload = $request->validated();
        $replayed = $idempotency->replay($request, 'checkout.shipping-address', $payload);

        if ($replayed !== null) {
            return (new CheckoutResource($replayed))->response()->header('Idempotency-Replayed', 'true');
        }

        $checkout->storeShippingAddress($payload, $request->user('web'));
        $result = $checkout->stateData($request->user('web'));
        $idempotency->remember($request, 'checkout.shipping-address', $payload, $result);

        return new CheckoutResource($result);
    }
}
