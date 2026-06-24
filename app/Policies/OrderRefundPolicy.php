<?php

namespace App\Policies;

use App\Models\OrderRefund;
use App\Models\User;

class OrderRefundPolicy
{
    public function view(User $user, OrderRefund $refund): bool
    {
        return $user->isCustomer()
            && $refund->order?->user_id === $user->id;
    }
}
