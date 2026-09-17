<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\ActionResource;
use App\Services\Auth\CustomerPasswordResetService;
use App\Support\Api\RequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Throwable;

final class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request, CustomerPasswordResetService $passwordResets): ActionResource
    {
        $expires = max(1, (int) config('auth.passwords.users.expire', 15));
        $message = sprintf(
            'If an active customer account matches that email, a password reset link will be sent. The link expires in %d minutes and can only be used once.',
            $expires,
        );

        try {
            $passwordResets->sendResetLink((string) $request->validated('email'));
        } catch (Throwable $exception) {
            report($exception);
        }

        return new ActionResource(['message' => $message]);
    }

    public function reset(ResetPasswordRequest $request, CustomerPasswordResetService $passwordResets): ActionResource|JsonResponse
    {
        $data = $request->validated();

        try {
            $status = $passwordResets->resetPassword(
                (string) $data['email'],
                (string) $data['token'],
                (string) $data['password'],
            );
        } catch (Throwable $exception) {
            report($exception);
            return $this->failure($request, 'The password could not be reset right now. Please try again or request a new reset link.');
        }

        if ($status !== Password::PasswordReset) {
            $message = match ($status) {
                Password::InvalidToken => 'This password reset link is invalid or has expired. Please request a new reset link.',
                Password::ResetThrottled => 'Please wait before trying to reset the password again.',
                default => 'The password could not be reset. Please request a new reset link.',
            };
            return $this->failure($request, $message);
        }

        return new ActionResource(['message' => 'Your password has been reset. You can now sign in with your new password.']);
    }

    private function failure(ResetPasswordRequest $request, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'request_id' => RequestId::from($request),
        ], 422);
    }
}
