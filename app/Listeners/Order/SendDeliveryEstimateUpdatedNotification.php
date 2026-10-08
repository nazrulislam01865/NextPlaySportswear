<?php

namespace App\Listeners\Order;

use App\Events\DeliveryEstimateUpdated;
use App\Services\Email\TransactionalEmailManager;
use Throwable;

final class SendDeliveryEstimateUpdatedNotification
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {
    }

    public function handle(DeliveryEstimateUpdated $event): void
    {
        try {
            $this->emails->deliveryEstimateUpdated(
                $event->shipment,
                $event->oldEstimate,
                $event->holidayReason,
                $event->order
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
