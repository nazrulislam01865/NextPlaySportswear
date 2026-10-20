<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Display an instant in the viewer's browser timezone without changing UTC
 * persistence, business dates, or application-wide timezone settings.
 *
 * The UTC rendering remains useful for clients without JavaScript. The shared
 * browser formatter upgrades the <time> element to a local date/time on load.
 */
final class ViewerTime
{
    public static function render(DateTimeInterface|string|null $date, string $format = 'M j, Y · g:i A'): ?HtmlString
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            $instant = $date instanceof DateTimeInterface
                ? CarbonImmutable::instance($date)->utc()
                : CarbonImmutable::parse($date, 'UTC')->utc();
        } catch (Throwable) {
            return null;
        }

        $instantUtc = $instant->format('Y-m-d\TH:i:s.u\Z');
        $textUtc = $instant->format($format);

        return new HtmlString(sprintf(
            '<time datetime="%s" data-np-local-time data-np-php-format="%s" title="UTC; your device timezone will be used when available">%s</time>',
            e($instantUtc),
            e($format),
            e($textUtc)
        ));
    }
}
