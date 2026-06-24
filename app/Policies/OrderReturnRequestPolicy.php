<?php

namespace App\Policies;

use App\Models\OrderReturnRequest;
use App\Models\User;

class OrderReturnRequestPolicy
{
    public function view(User $user, OrderReturnRequest $returnRequest): bool
    {
        return $user->isCustomer()
            && $returnRequest->user_id === $user->id;
    }
}
