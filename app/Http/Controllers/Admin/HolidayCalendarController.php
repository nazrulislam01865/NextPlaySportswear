<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayCalendarRequest;
use App\Models\CountryCallingCode;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Services\Shipping\HolidayCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HolidayCalendarController extends Controller
{
    public function __construct(
        private readonly HolidayCalendarService $holidayService
    ) {
    }

    /**
     * Display Holiday Calendars List (Screen 1 in Blueprint).
     */
    public function index(Request $request): View
    {
        $query = HolidayCalendar::query()
            ->withCount('dates')
            ->latest('year')
            ->orderBy('name');

        if ($request->filled('country')) {
            $query->forCountry($request->query('country'));
        }

        if ($request->filled('year')) {
            $query->where('year', (int) $request->query('year'));
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->query('search')) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('country_name', 'like', $search)
                    ->orWhere('country_code', 'like', $search);
            });
        }

        $calendars = $query->paginate($this->adminPerPage(15))->withQueryString();

        $availableCountries = HolidayCalendar::query()
            ->select(['country_code', 'country_name'])
            ->distinct()
            ->orderBy('country_name')
            ->get();

        $availableYears = HolidayCalendar::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([(int) date('Y')]);
        }

        return view('admin.holiday-calendars.index', [
            'calendars' => $calendars,
            'availableCountries' => $availableCountries,
            'availableYears' => $availableYears,
            'currentCountry' => (string) $request->query('country', ''),
            'currentYear' => (string) $request->query('year', ''),
            'currentStatus' => (string) $request->query('status', ''),
            'currentSearch' => (string) $request->query('search', ''),
        ]);
    }

    /**
     * Show form to create a new Holiday Calendar (Screen 2 in Blueprint).
     */
    public function create(): View
    {
        $calendar = new HolidayCalendar([
            'year' => (int) date('Y'),
            'is_active' => true,
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
        ]);

        return view('admin.holiday-calendars.create', [
            'calendar' => $calendar,
            'countries' => $this->availableCountryList(),
            'dates' => [],
        ]);
    }

    /**
     * Store newly created Holiday Calendar and its holiday dates.
     */
    public function store(HolidayCalendarRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $datesData = (array) ($data['dates'] ?? []);
        unset($data['dates']);

        $data['created_by'] = auth('admin')->id();

        $calendar = DB::transaction(function () use ($data, $datesData) {
            $calendar = HolidayCalendar::query()->create($data);

            $seenDates = [];
            foreach ($datesData as $dateItem) {
                $dateStr = ! empty($dateItem['date']) ? substr(trim((string) $dateItem['date']), 0, 10) : '';
                $nameStr = ! empty($dateItem['name']) ? trim((string) $dateItem['name']) : '';

                if ($dateStr !== '' && $nameStr !== '' && ! isset($seenDates[$dateStr])) {
                    $seenDates[$dateStr] = true;
                    HolidayCalendarDate::query()->create([
                        'holiday_calendar_id' => $calendar->id,
                        'date' => $dateStr,
                        'name' => $nameStr,
                        'notes' => $dateItem['notes'] ?? null,
                    ]);
                }
            }

            return $calendar;
        });

        $this->holidayService->clearHolidayCache($calendar->country_name, $calendar->year);

        $notificationNote = '';
        if ($calendar->is_active) {
            $recalc = $this->holidayService->recalculateOpenOrdersForCountry($calendar->country_name);
            if ($recalc['updated'] > 0) {
                $notificationNote = " Recalculated {$recalc['updated']} open orders and notified {$recalc['notified']} affected customer shipments.";
            }
        }

        return redirect()
            ->route('admin.holiday-calendars.index')
            ->with('status', "Holiday calendar '{$calendar->name}' created successfully.{$notificationNote}");
    }

    /**
     * Show form to edit an existing Holiday Calendar (Screen 2 in Blueprint).
     */
    public function edit(HolidayCalendar $holidayCalendar): View
    {
        $holidayCalendar->loadMissing('dates');

        $dates = $holidayCalendar->dates->map(fn (HolidayCalendarDate $d) => [
            'id' => $d->id,
            'date' => $d->date->format('Y-m-d'),
            'name' => $d->name,
            'notes' => $d->notes ?? '',
        ])->values()->all();

        return view('admin.holiday-calendars.edit', [
            'calendar' => $holidayCalendar,
            'countries' => $this->availableCountryList(),
            'dates' => $dates,
        ]);
    }

    /**
     * Update an existing Holiday Calendar and sync its dates.
     */
    public function update(HolidayCalendarRequest $request, HolidayCalendar $holidayCalendar): RedirectResponse
    {
        $data = $request->validated();
        $datesData = (array) ($data['dates'] ?? []);
        unset($data['dates']);

        $data['updated_by'] = auth('admin')->id();

        DB::transaction(function () use ($holidayCalendar, $data, $datesData) {
            $holidayCalendar->update($data);

            $holidayCalendar->dates()->delete();

            $seenDates = [];
            foreach ($datesData as $dateItem) {
                $dateStr = ! empty($dateItem['date']) ? substr(trim((string) $dateItem['date']), 0, 10) : '';
                $nameStr = ! empty($dateItem['name']) ? trim((string) $dateItem['name']) : '';

                if ($dateStr !== '' && $nameStr !== '' && ! isset($seenDates[$dateStr])) {
                    $seenDates[$dateStr] = true;
                    HolidayCalendarDate::query()->create([
                        'holiday_calendar_id' => $holidayCalendar->id,
                        'date' => $dateStr,
                        'name' => $nameStr,
                        'notes' => $dateItem['notes'] ?? null,
                    ]);
                }
            }
        });

        $this->holidayService->clearHolidayCache($holidayCalendar->country_name, $holidayCalendar->year);

        $notificationNote = '';
        if ($holidayCalendar->is_active) {
            $recalc = $this->holidayService->recalculateOpenOrdersForCountry($holidayCalendar->country_name);
            if ($recalc['updated'] > 0) {
                $notificationNote = " Recalculated {$recalc['updated']} open orders and notified {$recalc['notified']} affected customer shipments.";
            }
        }

        return redirect()
            ->route('admin.holiday-calendars.index')
            ->with('status', "Holiday calendar '{$holidayCalendar->name}' updated successfully.{$notificationNote}");
    }

    /**
     * Delete a Holiday Calendar.
     */
    public function destroy(HolidayCalendar $holidayCalendar): RedirectResponse
    {
        $country = $holidayCalendar->country_name;
        $year = $holidayCalendar->year;
        $wasActive = $holidayCalendar->is_active;
        $name = $holidayCalendar->name;

        $holidayCalendar->delete();
        $this->holidayService->clearHolidayCache($country, $year);

        if ($wasActive) {
            $this->holidayService->recalculateOpenOrdersForCountry($country);
        }

        return redirect()
            ->route('admin.holiday-calendars.index')
            ->with('status', "Holiday calendar '{$name}' removed.");
    }

    /**
     * Toggle active/inactive status directly from list or edit page.
     */
    public function toggleStatus(HolidayCalendar $holidayCalendar): RedirectResponse
    {
        $holidayCalendar->is_active = ! $holidayCalendar->is_active;
        $holidayCalendar->updated_by = auth('admin')->id();
        $holidayCalendar->save();

        $this->holidayService->clearHolidayCache($holidayCalendar->country_name, $holidayCalendar->year);

        $recalc = $this->holidayService->recalculateOpenOrdersForCountry($holidayCalendar->country_name);
        $statusLabel = $holidayCalendar->is_active ? 'activated' : 'deactivated';

        $note = $recalc['updated'] > 0
            ? " Recalculated {$recalc['updated']} open orders."
            : '';

        return back()->with('status', "Holiday calendar '{$holidayCalendar->name}' {$statusLabel}.{$note}");
    }

    /**
     * List of countries formatted for dropdowns with flag emojis.
     *
     * @return array<int, array{code: string, name: string, flag: string}>
     */
    private function availableCountryList(): array
    {
        $countryModels = CountryCallingCode::query()->active()->ordered()->get();

        if ($countryModels->isNotEmpty()) {
            return $countryModels->map(fn (CountryCallingCode $c) => [
                'code' => $c->iso_code,
                'name' => $c->country_name,
                'flag' => $c->flagEmoji(),
            ])->all();
        }

        return [
            ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
            ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸'],
            ['code' => 'CA', 'name' => 'Canada', 'flag' => '🇨🇦'],
            ['code' => 'AU', 'name' => 'Australia', 'flag' => '🇦🇺'],
            ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪'],
            ['code' => 'FR', 'name' => 'France', 'flag' => '🇫🇷'],
            ['code' => 'IE', 'name' => 'Ireland', 'flag' => '🇮🇪'],
            ['code' => 'NZ', 'name' => 'New Zealand', 'flag' => '🇳🇿'],
        ];
    }
}
