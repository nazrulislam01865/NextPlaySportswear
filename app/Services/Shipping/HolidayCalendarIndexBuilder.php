<?php

namespace App\Services\Shipping;

use App\Models\HolidayCalendarDate;
use Carbon\CarbonImmutable;

/** Immutable, serializable country-year index. No Eloquent models enter the cache. */
final class HolidayCalendarIndexBuilder
{
    public function __construct(private readonly HolidayCalendarRevisionStore $revisions) {}

    public function build(string $country, int $year, int $revision): array
    {
        $code = $this->revisions->countryCode($country);
        $name = \App\Support\CountryCatalog::find($code)['name'] ?? $country;
        $rows = HolidayCalendarDate::query()
            ->whereHas('calendar', fn ($q) => $q->active()->where('year', $year)
                ->where(function ($q) use ($code, $name) {
                    $q->where('country_code', $code)->orWhere('country_name', $name);
                }))
            ->orderBy('date')->orderBy('holiday_calendar_id')->orderBy('id')
            ->get(['id', 'holiday_calendar_id', 'date', 'name', 'notes']);
        $holidays = [];
        foreach ($rows as $row) {
            $date = $row->date->format('Y-m-d');
            // Same-day duplicates exclude the day once. Highest ordered calendar/date
            // row wins the displayed name, matching the existing Eloquent keyBy behavior.
            if (substr($date, 0, 4) === (string) $year) {
                $holidays[$date] = ['name' => $row->name, 'notes' => $row->notes];
            }
        }
        $first = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'UTC');
        $days = $first->isLeapYear() ? 366 : 365;
        $prefix = [0];
        for ($day = 1; $day <= $days; $day++) {
            $date = $first->addDays($day - 1);
            $working = ! $date->isWeekend() && ! isset($holidays[$date->toDateString()]);
            $prefix[$day] = $prefix[$day - 1] + ($working ? 1 : 0);
        }
        return [
            'country' => $code, 'year' => $year, 'revision' => $revision,
            'policy' => 1, 'days' => $days, 'prefix' => $prefix,
            'holidays' => $holidays, 'sorted_dates' => array_keys($holidays),
        ];
    }
}
