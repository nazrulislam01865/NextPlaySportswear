<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isCustomer() && $order->user_id === $user->id;
    }

    public function pay(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canPay();
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
