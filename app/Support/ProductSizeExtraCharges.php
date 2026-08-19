<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ProductSizeExtraCharges
{
    /**
     * Normalize both the current indexed size-charge rows and the previous
     * associative size_price_adjustments payload into one code => amount map.
     *
     * @param  array<string, mixed>  $groupData
     * @return array<string, float>
     */
    public static function adjustments(array $groupData): array
    {
        $adjustments = [];

        foreach ((array) ($groupData['size_price_adjustments'] ?? []) as $code => $amount) {
            $normalizedCode = trim((string) $code);
            if ($normalizedCode === '') {
                continue;
            }

            $adjustments[$normalizedCode] = self::normalizeAmount($amount);
        }

        foreach ((array) ($groupData['size_charges'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $code = trim((string) ($row['code'] ?? '')) ?: Str::slug($label);
            if ($code === '') {
                continue;
            }

            $adjustments[$code] = self::normalizeAmount($row['amount'] ?? 0);
        }

        return $adjustments;
    }

    /**
     * @param  array<string, mixed>  $groupData
     */
    public static function enabled(array $groupData): bool
    {
        if ((bool) ($groupData['has_size_extra_charges'] ?? false)) {
            return true;
        }

        foreach (self::adjustments($groupData) as $amount) {
            if ($amount > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build editor lookup aliases for one persisted size so edit hydration stays
     * stable even when a master size code/label was normalized after the product
     * was originally saved.
     *
     * @return array<string, float>
     */
    public static function editorAdjustmentAliases(string $code, string $label, mixed $amount): array
    {
        $normalizedAmount = self::normalizeAmount($amount);
        $keys = array_filter(array_unique([
            trim($code),
            trim($label),
            Str::slug($label),
        ]), static fn (string $key): bool => $key !== '');

        $aliases = [];
        foreach ($keys as $key) {
            $aliases[$key] = $normalizedAmount;
        }

        return $aliases;
    }

    /**
     * @param  array<string, float|int|string|null>  $adjustments
     */
    public static function amountFor(array $adjustments, string $code, string $label): float
    {
        $normalizedCode = trim($code) !== '' ? trim($code) : Str::slug($label);
        $fallbackCode = Str::slug($label);
        $value = $adjustments[$normalizedCode] ?? $adjustments[$fallbackCode] ?? 0;

        return self::normalizeAmount($value);
    }

    private static function normalizeAmount(mixed $value): float
    {
        return max(0, is_numeric($value) ? round((float) $value, 2) : 0);
    }
}
