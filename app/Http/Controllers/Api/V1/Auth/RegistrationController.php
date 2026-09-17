<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Auth\CustomerSessionResource;
use App\Services\Auth\CustomerRegistrationService;
use App\Services\Auth\CustomerSessionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Throwable;

final class RegistrationController extends Controller
{
    public function __invoke(
        RegisterRequest $request,
        CustomerRegistrationService $registration,
        CustomerSessionService $sessions,
    ): JsonResponse {
        $customer = $registration->register($request->validated());
        $verificationDelivery = 'sent';

        try {
            event(new Registered($customer));
        } catch (Throwable $exception) {
            report($exception);
            $verificationDelivery = 'failed';
        }

        $sessions->start($request, $customer);

        return (new CustomerSessionResource([
            'customer' => $customer,
            'verification_delivery' => $verificationDelivery,
        ]))->response()->setStatusCode(201);
    }
}
