<?php

namespace App\Jobs\Shipping;

use App\Services\Shipping\HolidayCalendarRevisionStore;
use App\Services\Shipping\HolidayCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Fan-out bounded batches; never materialize the entire matching order set. */
final class ScheduleHolidayRecalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $country, public int $year, public int $revision) {}

    public function handle(HolidayCalendarService $calendar, HolidayCalendarRevisionStore $revisions): void
    {
        if ($revisions->current($this->country, $this->year) !== $this->revision) return;
        $size = (int) config('nextplay_shipping.holiday_batch_size', 150);
        $calendar->openOrdersQuery($this->country)
            ->select('orders.id')->chunkById($size, function ($orders) use ($revisions): bool {
                if ($revisions->current($this->country, $this->year) !== $this->revision) return false;
                RecalculateHolidayOrdersBatch::dispatch(
                    $this->country, $this->year, $this->revision, $orders->pluck('id')->all()
                )->onQueue(config('nextplay_shipping.holiday_queue', 'default'));
                return true;
            }, 'orders.id', 'id');
    }
}
