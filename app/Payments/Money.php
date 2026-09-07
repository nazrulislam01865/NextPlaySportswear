<?php

namespace App\Payments;

use InvalidArgumentException;

final class Money
{
    /**
     * Stripe and most gateways expect integer minor units. Keep conversion in
     * one place so controllers never perform floating point money arithmetic.
     */
    public static function toMinor(float|string $amount, string $currency): int
    {
        $currency = strtoupper($currency);
        $zeroDecimal = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        $factor = in_array($currency, $zeroDecimal, true) ? 1 : 100;

        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return (int) round(((float) $amount) * $factor, 0, PHP_ROUND_HALF_UP);
    }
}
