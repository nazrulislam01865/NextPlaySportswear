<?php

namespace Tests\Feature\Admin;

use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayCalendarCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.holiday-calendars.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_holiday_calendars_index(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'UK Bank Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.holiday-calendars.index'));

        $response->assertOk();
        $response->assertSee('UK Bank Holidays 2026');
        $response->assertSee('United Kingdom');
        $response->assertSee('2026');
        $response->assertSee('Active');
    }

    public function test_admin_can_filter_holiday_calendars(): void
    {
        HolidayCalendar::query()->create([
            'name' => 'UK Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendar::query()->create([
            'name' => 'US Holidays 2025',
            'country_code' => 'US',
            'country_name' => 'United States',
            'year' => 2025,
            'is_active' => false,
        ]);

        // Filter by country
        $ukResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.holiday-calendars.index', ['country' => 'United Kingdom']));
        $ukResponse->assertSee('UK Holidays 2026');
        $ukResponse->assertDontSee('US Holidays 2025');

        // Filter by status inactive
        $inactiveResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.holiday-calendars.index', ['status' => 'inactive']));
        $inactiveResponse->assertSee('US Holidays 2025');
        $inactiveResponse->assertDontSee('UK Holidays 2026');
    }

    public function test_admin_can_open_create_page(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.holiday-calendars.create'));
        $response->assertOk();
        $response->assertSee('Create Holiday Calendar');
        $response->assertSee('Calendar Details');
        $response->assertSee('Holiday Dates');
    }

    public function test_admin_can_store_holiday_calendar_with_dates(): void
    {
        $payload = [
            'name' => 'Canada Public Holidays 2026',
            'country' => 'CA|Canada',
            'year' => 2026,
            'is_active' => '1',
            'description' => 'National stat holidays',
            'dates' => [
                ['date' => '2026-07-01', 'name' => 'Canada Day', 'notes' => 'Federal closure'],
                ['date' => '2026-12-25', 'name' => 'Christmas Day', 'notes' => null],
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.holiday-calendars.store'), $payload);

        $response->assertRedirect(route('admin.holiday-calendars.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('holiday_calendars', [
            'name' => 'Canada Public Holidays 2026',
            'country_code' => 'CA',
            'country_name' => 'Canada',
            'year' => 2026,
            'is_active' => true,
        ]);

        $calendar = HolidayCalendar::query()->where('name', 'Canada Public Holidays 2026')->firstOrFail();
        $this->assertCount(2, $calendar->dates);
        $this->assertDatabaseHas('holiday_calendar_dates', [
            'holiday_calendar_id' => $calendar->id,
            'name' => 'Canada Day',
        ]);
        $this->assertSame('2026-07-01', $calendar->dates->sortBy('date')->first()->date->format('Y-m-d'));
    }

    public function test_admin_can_open_edit_page(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'France Holidays 2026',
            'country_code' => 'FR',
            'country_name' => 'France',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-07-14',
            'name' => 'Bastille Day',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.holiday-calendars.edit', $calendar));

        $response->assertOk();
        $response->assertSee('France Holidays 2026');
        $response->assertSee('Bastille Day');
    }

    public function test_admin_can_update_holiday_calendar_and_sync_dates(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'Australia Holidays 2026',
            'country_code' => 'AU',
            'country_name' => 'Australia',
            'year' => 2026,
            'is_active' => true,
        ]);

        $oldDate = HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-01-26',
            'name' => 'Australia Day',
        ]);

        $payload = [
            'name' => 'Australia National Holidays 2026',
            'country' => 'AU|Australia',
            'year' => 2026,
            'is_active' => '1',
            'dates' => [
                // Keep old date updated
                ['date' => '2026-01-26', 'name' => 'Australia Day (Observed)', 'notes' => 'Holiday'],
                // Add new date
                ['date' => '2026-04-25', 'name' => 'Anzac Day', 'notes' => 'Closure'],
            ],
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.holiday-calendars.update', $calendar), $payload);

        $response->assertRedirect(route('admin.holiday-calendars.index'));

        $calendar->refresh();
        $this->assertSame('Australia National Holidays 2026', $calendar->name);
        $this->assertCount(2, $calendar->dates);
        $this->assertDatabaseHas('holiday_calendar_dates', [
            'holiday_calendar_id' => $calendar->id,
            'name' => 'Australia Day (Observed)',
        ]);
        $this->assertDatabaseHas('holiday_calendar_dates', [
            'holiday_calendar_id' => $calendar->id,
            'name' => 'Anzac Day',
        ]);
    }

    public function test_admin_can_toggle_holiday_calendar_status(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'Germany Bank Holidays 2026',
            'country_code' => 'DE',
            'country_name' => 'Germany',
            'year' => 2026,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.holiday-calendars.toggle-status', $calendar));

        $response->assertSessionHas('status');
        $this->assertFalse($calendar->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.holiday-calendars.toggle-status', $calendar));

        $this->assertTrue($calendar->fresh()->is_active);
    }

    public function test_admin_can_delete_holiday_calendar(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'Temporary Calendar',
            'country_code' => 'NZ',
            'country_name' => 'New Zealand',
            'year' => 2026,
            'is_active' => false,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-02-06',
            'name' => 'Waitangi Day',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.holiday-calendars.destroy', $calendar));

        $response->assertRedirect(route('admin.holiday-calendars.index'));
        $this->assertDatabaseMissing('holiday_calendars', ['id' => $calendar->id]);
        $this->assertDatabaseMissing('holiday_calendar_dates', ['holiday_calendar_id' => $calendar->id]);
    }
}
