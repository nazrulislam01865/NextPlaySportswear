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

        $raw = trim((string) $amount);
        if (! preg_match('/^-?\d+(?:\.\d+)?$/D', $raw)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }
        $negative = str_starts_with($raw, '-');
        $unsigned = ltrim($raw, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $precision = $factor === 1 ? 0 : 2;
        $scaled = ((int) $whole) * $factor;
        $digits = str_pad($fraction, $precision + 1, '0');
        if ($precision > 0) {
            $scaled += (int) substr($digits, 0, $precision);
        }
        if ((int) ($digits[$precision] ?? '0') >= 5) {
            $scaled++;
        }
        return $negative ? -$scaled : $scaled;
    }

    public static function fromMinor(int $minor, string $currency): float
    {
        $zeroDecimal = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        return $minor / (in_array(strtoupper($currency), $zeroDecimal, true) ? 1 : 100);
    }
}
