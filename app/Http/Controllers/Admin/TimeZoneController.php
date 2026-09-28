<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TimeZoneRequest;
use App\Models\TimeZone;
use DateTimeZone as PhpDateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TimeZoneController extends Controller
{
    public function index(): View
    {
        return view('admin.time-zones.index', [
            'timeZones' => TimeZone::query()->ordered()->paginate($this->adminPerPage(30))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.time-zones.create', [
            'timeZone' => new TimeZone([
                'is_active' => true,
                'sort_order' => ((int) TimeZone::query()->max('sort_order')) + 10,
            ]),
            'identifiers' => PhpDateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(TimeZoneRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? (((int) TimeZone::query()->max('sort_order')) + 10));
        TimeZone::query()->create($data);

        return redirect()->route('admin.time-zones.index')
            ->with('status', 'Time zone created successfully.');
    }

    public function edit(TimeZone $timeZone): View
    {
        return view('admin.time-zones.edit', [
            'timeZone' => $timeZone,
            'identifiers' => PhpDateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(TimeZoneRequest $request, TimeZone $timeZone): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? $timeZone->sort_order ?? 0);
        $timeZone->update($data);

        return redirect()->route('admin.time-zones.index')
            ->with('status', 'Time zone updated successfully.');
    }

    public function destroy(TimeZone $timeZone): RedirectResponse
    {
        $timeZone->delete();

        return redirect()->route('admin.time-zones.index')
            ->with('status', 'Time zone removed from Master Data. Existing campaigns keep their saved time zone.');
    }
}
