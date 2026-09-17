<?php

namespace App\Services\Checkout;

use App\Exceptions\CheckoutApiConflictException;

final class CheckoutApiGuard
{
    public function requireCart(CheckoutService $checkout): void
    {
        if (! $checkout->hasCheckoutItems()) {
            throw new CheckoutApiConflictException(
                'Your cart is empty.',
                'checkout_cart_empty',
            );
        }
    }

    public function requireStep(CheckoutService $checkout, string $requestedStep): void
    {
        $this->requireCart($checkout);
        $missing = $checkout->firstIncompleteStepBefore($requestedStep);

        if ($missing !== null) {
            throw new CheckoutApiConflictException(
                (string) ($missing['message'] ?? 'Complete the previous checkout step before continuing.'),
                'checkout_step_incomplete',
                [
                    'required_step' => (string) ($missing['key'] ?? ''),
                    'requested_step' => $requestedStep,
                ],
            );
        }
    }
}
