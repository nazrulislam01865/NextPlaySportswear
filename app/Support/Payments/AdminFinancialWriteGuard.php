<?php

namespace App\Support\Payments;

use Illuminate\Validation\ValidationException;

/**
 * Financial values are exclusively the responsibility of verified payment workflows.
 * Admin form requests and domain services use the same denylist so direct service
 * calls cannot bypass HTTP validation. This does not alter provider webhooks.
 */
final class AdminFinancialWriteGuard
{
    private const FORBIDDEN = [
        'payment_status', 'paid_at', 'payment_method', 'payment_method_id',
        'payment_amount', 'paid_amount', 'amount', 'subtotal', 'grand_total',
        'customization_total', 'discount_total', 'shipping_total', 'tax_total',
        'rural_surcharge_total', 'product_shipping_total',
        'payments', 'order_payments', 'order_payment_id',
        'refund_status', 'refund_amount', 'approved_amount', 'refunded_amount',
        'refunds', 'order_refunds', 'order_refund_id', 'processed_at',
        'provider', 'gateway', 'provider_reference', 'provider_payment_id',
        'provider_session_id', 'refund_reference', 'currency',
    ];

    /** @return array<string, array<int, string>> */
    public static function requestRules(): array
    {
        return array_fill_keys(self::FORBIDDEN, ['missing']);
    }

    public static function assertNoFinancialFields(array $payload): void
    {
        $supplied = array_intersect(self::FORBIDDEN, array_keys($payload));
        if ($supplied === []) {
            return;
        }

        throw ValidationException::withMessages(array_fill_keys(
            $supplied,
            'Financial information is read-only for NextPlay administrators.',
        ));
    }
}
