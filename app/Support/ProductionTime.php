<?php

namespace App\Support;

final class ProductionTime
{
    /** @return array{minimum_days:int, maximum_days:int, display:string}|null */
    public static function parse(string|int|null $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['–', '—'], '-', strtolower($value));
        $normalized = preg_replace('/\s*(?:working|business|calendar)?\s*days?\s*$/i', '', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if (preg_match('/^(\d+)\s*(?:-|to)\s*(\d+)$/i', $normalized, $matches) === 1) {
            $minimum = (int) $matches[1];
            $maximum = (int) $matches[2];

            if ($minimum < 0 || $maximum < $minimum || $maximum > 3650) {
                return null;
            }

            return [
                'minimum_days' => $minimum,
                'maximum_days' => $maximum,
                'display' => self::format($minimum, $maximum),
            ];
        }

        if (preg_match('/^(\d+)$/', $normalized, $matches) === 1) {
            $days = (int) $matches[1];
            if ($days < 0 || $days > 3650) {
                return null;
            }

            return [
                'minimum_days' => $days,
                'maximum_days' => $days,
                'display' => self::format($days, $days),
            ];
        }

        return null;
    }

    public static function format(int|string|null $minimum, int|string|null $maximum = null): string
    {
        $minimum = max(0, (int) ($minimum ?? 0));
        $maximum = $maximum === null || $maximum === '' ? $minimum : max($minimum, (int) $maximum);

        if ($minimum === $maximum) {
            return $minimum.' '.($minimum === 1 ? 'day' : 'days');
        }

        return $minimum.'-'.$maximum.' days';
    }
}
