<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryEstimateUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ?OrderShipment $shipment = null,
        public readonly ?string $oldEstimate = null,
        public readonly ?string $holidayReason = null,
        public readonly ?Order $order = null,
    ) {
    }
}
