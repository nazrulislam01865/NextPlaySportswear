<?php

namespace App\Jobs\Shipping;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Durable, uniquely keyed email-enqueue request; respects retries and parallel workers. */
final class SendHolidayEtaNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public string $eventKey) {}

    public function handle(TransactionalEmailManager $emails): void
    {
        $claimed = DB::table('holiday_eta_notifications')->where('event_key', $this->eventKey)
            ->whereNull('queued_at')->where(function ($query): void {
                $query->whereNull('processing_at')->orWhere('processing_at', '<', now()->subMinutes(10));
            })->update(['processing_at' => now(), 'updated_at' => now()]);
        if (! $claimed) return;

        try {
            $row = DB::table('holiday_eta_notifications')->where('event_key', $this->eventKey)->first();
            if (! $row) return;
            $payload = json_decode($row->payload, true) ?: [];
            $order = Order::find($row->order_id);
            if (! $order) throw new RuntimeException('Order missing for holiday ETA email outbox row');
            // An email can be delayed while a newer holiday edit changes the ETA.
            // Never send a superseded estimate with an old reason and current dates.
            if (array_key_exists('new_start_date', $payload) && array_key_exists('new_end_date', $payload)
                && ($order->estimated_delivery_start_at?->toDateString() !== $payload['new_start_date']
                    || $order->estimated_delivery_end_at?->toDateString() !== $payload['new_end_date'])) {
                DB::table('holiday_eta_notifications')->where('event_key', $this->eventKey)
                    ->whereNull('queued_at')->update([
                        'queued_at' => now(), 'processing_at' => null, 'updated_at' => now(),
                    ]);
                return;
            }
            $shipment = ! empty($payload['shipment_id']) ? OrderShipment::find($payload['shipment_id']) : null;
            if (! $emails->deliveryEstimateUpdated(
                $shipment, $payload['old_estimate'] ?? null, $payload['reason'] ?? null, $order
            )) {
                throw new RuntimeException('Holiday ETA notification could not be queued');
            }
            DB::table('holiday_eta_notifications')->where('event_key', $this->eventKey)
                ->whereNull('queued_at')->update(['queued_at' => now(), 'processing_at' => null, 'updated_at' => now()]);
        } catch (Throwable $exception) {
            DB::table('holiday_eta_notifications')->where('event_key', $this->eventKey)
                ->whereNull('queued_at')->update(['processing_at' => null, 'updated_at' => now()]);
            throw $exception;
        }
    }
}
