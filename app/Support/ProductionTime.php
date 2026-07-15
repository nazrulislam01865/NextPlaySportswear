<?php

namespace App\Support;

final class ProductionTime
{
    /** @return array{minimum_days:int, maximum_days:int, display:string}|null */
    public static function parse(string|int|null $value): ?array
    {
        $original = trim((string) $value);
        if ($original === '') {
            return null;
        }

        $normalized = html_entity_decode($original, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = str_replace(["\xc2\xa0", '–', '—'], [' ', '-', '-'], strtolower($normalized));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if (preg_match('/^(?:to\s+be\s+confirmed|to\s+confirm|tbc|tbd|confirm(?:ed)?\s+later|contact\s+us|contact\s+for\s+(?:quote|quotation)|custom\s+(?:quote|quotation)|quote\s+based|upon\s+request|on\s+request|n\/?a|not\s+applicable)$/i', $normalized) === 1) {
            return [
                'minimum_days' => 0,
                'maximum_days' => 0,
                'display' => self::displayText($original),
            ];
        }

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

    private static function displayText(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        if (preg_match('/^(?:tbc|tbd|to\s+confirm|confirm(?:ed)?\s+later)$/i', $value) === 1) {
            return 'To be confirmed';
        }

        if (preg_match('/^(?:n\/?a|not\s+applicable)$/i', $value) === 1) {
            return 'N/A';
        }

        return $value !== '' ? ucfirst($value) : 'To be confirmed';
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
