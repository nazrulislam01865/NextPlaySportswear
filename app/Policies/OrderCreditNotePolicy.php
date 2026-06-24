<?php

namespace App\Policies;

use App\Models\OrderCreditNote;
use App\Models\User;

class OrderCreditNotePolicy
{
    public function view(User $user, OrderCreditNote $creditNote): bool
    {
        return $user->isCustomer()
            && $creditNote->order?->user_id === $user->id;
    }
}
