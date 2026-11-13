<?php

namespace App\Services\Shipping;

use App\Support\CountryCatalog;
use Illuminate\Support\Facades\DB;

/** DB-backed generation: a cache entry is never authoritative without its committed revision. */
final class HolidayCalendarRevisionStore
{
    private static ?array $names = null;

    public function countryCode(string $country): string
    {
        $value = trim($country);
        $upper = strtoupper($value);
        if ($value === '' || in_array($upper, ['GB', 'UK', 'GREAT BRITAIN', 'UNITED KINGDOM'], true)) return 'GB';
        if (in_array($upper, ['USA', 'UNITED STATES', 'UNITED STATES OF AMERICA', 'US'], true)) return 'US';
        if (self::$names === null) {
            self::$names = [];
            foreach (CountryCatalog::all() as $record) {
                self::$names[strtolower($record['name'])] = $record['code'];
            }
        }
        return self::$names[strtolower($value)] ?? (strlen($upper) === 2 ? $upper : $upper);
    }

    public function current(string $country, int $year): int
    {
        return (int) (DB::table('holiday_calendar_revisions')
            ->where('country_code', $this->countryCode($country))->where('year', $year)
            ->value('revision') ?? 0);
    }

    /** A shared transaction lock allows parallel readers but blocks a concurrent revision bump. */
    public function lockedCurrent(string $country, int $year): int
    {
        return (int) (DB::table('holiday_calendar_revisions')
            ->where('country_code', $this->countryCode($country))->where('year', $year)
            ->sharedLock()->value('revision') ?? 0);
    }

    /** Called inside the same transaction as the calendar edit, including its date rows. */
    public function advance(string $country, int $year): int
    {
        $code = $this->countryCode($country);
        DB::table('holiday_calendar_revisions')->insertOrIgnore([
            'country_code' => $code, 'year' => $year, 'revision' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('holiday_calendar_revisions')->where('country_code', $code)
            ->where('year', $year)->increment('revision', 1, ['updated_at' => now()]);

        return $this->current($code, $year);
    }
}
