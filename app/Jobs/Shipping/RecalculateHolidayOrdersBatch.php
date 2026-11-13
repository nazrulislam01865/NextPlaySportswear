<?php

namespace App\Jobs\Shipping;

use App\Models\Order;
use App\Services\Shipping\HolidayCalendarRevisionStore;
use App\Services\Shipping\HolidayCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/** Locks orders before changes and skips superseded calendar revisions. */
final class RecalculateHolidayOrdersBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 120;

    /** @param array<int,int> $orderIds */
    public function __construct(public string $country, public int $year, public int $revision, public array $orderIds) {}

    public function handle(HolidayCalendarService $calendar, HolidayCalendarRevisionStore $revisions): void
    {
        if ($revisions->current($this->country, $this->year) !== $this->revision) return;
        foreach ($this->orderIds as $orderId) {
            $current = DB::transaction(function () use ($calendar, $revisions, $orderId): bool {
                $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();
                if (! $order || in_array($order->status, ['completed', 'delivered', 'cancelled', 'shipped'], true)) return true;
                if ($calendar->resolveOrderCountry($order) !== $calendar->normalizeCountry($this->country)) return true;
                if (! $calendar->orderWindowTouchesYear($order, $this->year)) return true;
                if ($revisions->lockedCurrent($this->country, $this->year) !== $this->revision) return false;
                $calendar->recalculateOrderEta($order, null, true);
                return true;
            }, 3);
            if (! $current) return; // Superseded by an edit; a newer batch will finish the work.
        }
    }
}
