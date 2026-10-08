<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use Illuminate\Database\Seeder;

class HolidayCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $calendars = [
            [
                'name' => 'UK Public Holidays 2026',
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'year' => 2026,
                'is_active' => true,
                'description' => 'Official UK public and bank holidays for 2026.',
                'dates' => [
                    ['date' => '2026-01-01', 'name' => "New Year's Day"],
                    ['date' => '2026-04-03', 'name' => 'Good Friday'],
                    ['date' => '2026-04-06', 'name' => 'Easter Monday'],
                    ['date' => '2026-05-04', 'name' => 'Early May Bank Holiday'],
                    ['date' => '2026-05-25', 'name' => 'Spring Bank Holiday'],
                    ['date' => '2026-08-31', 'name' => 'Summer Bank Holiday'],
                    ['date' => '2026-12-25', 'name' => 'Christmas Day'],
                    ['date' => '2026-12-28', 'name' => 'Boxing Day (Observed)'],
                ],
            ],
            [
                'name' => 'UK Carrier Closures 2026',
                'country_code' => 'GB',
                'country_name' => 'United Kingdom',
                'year' => 2026,
                'is_active' => true,
                'description' => 'Regional parcel carrier and postal service closure dates.',
                'dates' => [
                    ['date' => '2026-12-24', 'name' => 'Christmas Eve Carrier Half-Day'],
                    ['date' => '2026-12-29', 'name' => 'Carrier Hub Maintenance Day'],
                    ['date' => '2026-12-31', 'name' => "New Year's Eve Early Closure"],
                ],
            ],
            [
                'name' => 'USA Federal Holidays 2026',
                'country_code' => 'US',
                'country_name' => 'United States',
                'year' => 2026,
                'is_active' => true,
                'description' => 'Official federal holidays recognized across the United States.',
                'dates' => [
                    ['date' => '2026-01-01', 'name' => "New Year's Day"],
                    ['date' => '2026-01-19', 'name' => 'Martin Luther King Jr. Day'],
                    ['date' => '2026-02-16', 'name' => "Washington's Birthday"],
                    ['date' => '2026-05-25', 'name' => 'Memorial Day'],
                    ['date' => '2026-06-19', 'name' => 'Juneteenth National Independence Day'],
                    ['date' => '2026-07-03', 'name' => 'Independence Day (Observed)'],
                    ['date' => '2026-09-07', 'name' => 'Labor Day'],
                    ['date' => '2026-10-12', 'name' => 'Columbus Day'],
                    ['date' => '2026-11-11', 'name' => 'Veterans Day'],
                    ['date' => '2026-11-26', 'name' => 'Thanksgiving Day'],
                    ['date' => '2026-12-25', 'name' => 'Christmas Day'],
                ],
            ],
            [
                'name' => 'Canada Holidays 2026',
                'country_code' => 'CA',
                'country_name' => 'Canada',
                'year' => 2026,
                'is_active' => false,
                'description' => 'National statutory holidays for Canada.',
                'dates' => [
                    ['date' => '2026-01-01', 'name' => "New Year's Day"],
                    ['date' => '2026-02-16', 'name' => 'Family Day'],
                    ['date' => '2026-04-03', 'name' => 'Good Friday'],
                    ['date' => '2026-05-18', 'name' => 'Victoria Day'],
                    ['date' => '2026-07-01', 'name' => 'Canada Day'],
                    ['date' => '2026-08-03', 'name' => 'Civic Holiday'],
                    ['date' => '2026-09-07', 'name' => 'Labour Day'],
                    ['date' => '2026-10-12', 'name' => 'Thanksgiving Day'],
                    ['date' => '2026-11-11', 'name' => 'Remembrance Day'],
                    ['date' => '2026-12-25', 'name' => 'Christmas Day'],
                ],
            ],
        ];

        foreach ($calendars as $data) {
            $dates = $data['dates'];
            unset($data['dates']);

            $calendar = HolidayCalendar::query()->updateOrCreate(
                [
                    'name' => $data['name'],
                    'country_code' => $data['country_code'],
                    'year' => $data['year'],
                ],
                $data
            );

            foreach ($dates as $dateData) {
                HolidayCalendarDate::query()->updateOrCreate(
                    [
                        'holiday_calendar_id' => $calendar->id,
                        'date' => $dateData['date'],
                    ],
                    [
                        'name' => $dateData['name'],
                    ]
                );
            }
        }
    }
}
