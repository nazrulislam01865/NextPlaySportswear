<?php

namespace App\Services\Shipping;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;

/** Lower-bound search on non-decreasing working-day ranks; excludes start for N>0. */
final class IndexedBusinessDayCalculator
{
    /** @param callable(int):array $getIndex */
    public function add(CarbonInterface $starting, int $days, callable $getIndex): array
    {
        $start = Carbon::parse($starting);
        $year = (int) $start->year;
        $ordinal = (int) $start->dayOfYear; // Carbon's 1-based dayOfYear
        $remaining = max(0, $days);
        $seen = [];

        // A bounded horizon protects against corrupt calendars and unreasonably large user inputs.
        for ($attempt = 0; $attempt < 100; $attempt++, $year++) {
            $index = $getIndex($year);
            $prefix = $index['prefix'];
            $max = (int) $index['days'];
            $endRank = $prefix[$max];
            if ($days <= 0 && $attempt === 0 && $prefix[$ordinal] > $prefix[$ordinal - 1]) {
                return ['target_date' => $start, 'holidays_encountered' => []];
            }
            $candidate = $days <= 0 ? max(1, $ordinal) : $ordinal + 1;
            $rank = $days <= 0 ? $prefix[max(0, $ordinal - 1)] + 1 : $prefix[$ordinal] + $remaining;
            if ($rank <= $endRank) {
                $found = $this->lowerBound($prefix, $rank, $candidate, $max);
                $target = $start->copy()->setDate($year, 1, 1)->addDays($found - 1);
                $this->recordHolidays($seen, $index, $ordinal, $found, $days <= 0 && $attempt === 0);
                return ['target_date' => $target, 'holidays_encountered' => $seen];
            }
            $this->recordHolidays($seen, $index, $ordinal, $max, $days <= 0 && $attempt === 0);
            if ($days > 0) $remaining -= $endRank - $prefix[$ordinal];
            $ordinal = 0;
            // If N<=0 and the year was entirely closed, find the first working day next year.
            if ($days <= 0) $remaining = 0;
        }
        throw new DomainException('No working date found within the supported 100-year horizon.');
    }

    public function workingDaysBetween(array $index, int $startOrdinal, int $endOrdinal): int
    {
        return $index['prefix'][$endOrdinal] - $index['prefix'][$startOrdinal];
    }

    private function lowerBound(array $prefix, int $rank, int $left, int $right): int
    {
        while ($left < $right) {
            $middle = intdiv($left + $right, 2);
            if ($prefix[$middle] >= $rank) $right = $middle;
            else $left = $middle + 1;
        }
        return $left;
    }

    private function recordHolidays(array &$encountered, array $index, int $from, int $to, bool $includeStart): void
    {
        $firstOrdinal = $includeStart ? max(1, $from) : $from + 1;
        if ($firstOrdinal > $to) return;
        $firstDay = \Carbon\CarbonImmutable::create($index['year'], 1, 1, 0, 0, 0, 'UTC');
        $fromDate = $firstDay->addDays($firstOrdinal - 1)->toDateString();
        $untilDate = $firstDay->addDays($to - 1)->toDateString();
        $dates = $index['sorted_dates'];
        $left = 0;
        $right = count($dates);
        while ($left < $right) {
            $middle = intdiv($left + $right, 2);
            if ($dates[$middle] < $fromDate) $left = $middle + 1;
            else $right = $middle;
        }
        for ($i = $left, $count = count($dates); $i < $count; $i++) {
            $date = $dates[$i];
            if ($date > $untilDate) break;
            if (! $includeStart && \Carbon\CarbonImmutable::parse($date, 'UTC')->isWeekend()) continue;
            $encountered[$date] = $index['holidays'][$date]['name'];
        }
    }
}
