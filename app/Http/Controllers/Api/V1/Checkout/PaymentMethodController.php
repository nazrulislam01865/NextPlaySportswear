<?php
namespace App\Http\Controllers\Api\V1\Checkout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\PaymentMethodRequest;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutApiGuard;
use App\Services\Checkout\CheckoutService;
final class PaymentMethodController extends Controller
{
    public function __invoke(PaymentMethodRequest $request, CheckoutService $checkout, CheckoutApiGuard $guard): CheckoutResource
    {
        $guard->requireStep($checkout, 'payment');
        $checkout->storePaymentMethod($request->validated(), $request->user('web'));
        return new CheckoutResource($checkout->stateData($request->user('web')));
    }
}
