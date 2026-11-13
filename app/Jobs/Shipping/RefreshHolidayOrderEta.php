<?php

namespace App\Jobs\Shipping;

use App\Models\Order;
use App\Services\Shipping\HolidayCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/** Refresh the immutable order snapshot only when an approval becomes effective. */
final class RefreshHolidayOrderEta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $orderId) {}

    public function handle(HolidayCalendarService $calendar): void
    {
        DB::transaction(function () use ($calendar): void {
            $order = Order::query()->whereKey($this->orderId)->lockForUpdate()->first();
            if ($order && ! in_array($order->status, ['completed','delivered','cancelled','shipped'], true)) {
                // Artwork approval is not itself a holiday-change email event.
                $calendar->recalculateOrderEta($order, notify: false);
            }
        });
    }
}
