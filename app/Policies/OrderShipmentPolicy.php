<?php

namespace App\Policies;

use App\Models\OrderShipment;
use App\Models\User;

class OrderShipmentPolicy
{
    public function view(User $user, OrderShipment $shipment): bool
    {
        return $user->isCustomer()
            && $shipment->order?->user_id === $user->id;
    }
}
