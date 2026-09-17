<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActionResource;
use App\Http\Resources\Api\V1\Auth\VerificationStatusResource;
use App\Support\Api\RequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class EmailVerificationController extends Controller
{
    public function status(Request $request): VerificationStatusResource
    {
        return new VerificationStatusResource($request->user('web'));
    }

    public function resend(Request $request): ActionResource|JsonResponse
    {
        $customer = $request->user('web');

        if ($customer->hasVerifiedEmail()) {
            return new ActionResource(['message' => 'Your email address is already verified.']);
        }

        try {
            $customer->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => 'We could not send a verification email right now. Please try again shortly.',
                'request_id' => RequestId::from($request),
            ], 503);
        }

        return new ActionResource(['message' => 'Verification email sent.']);
    }
}
