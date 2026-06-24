<?php

namespace App\Policies;

use App\Models\OrderReturnAttachment;
use App\Models\User;

class OrderReturnAttachmentPolicy
{
    public function view(User $user, OrderReturnAttachment $attachment): bool
    {
        return $user->isCustomer()
            && $attachment->returnRequest?->user_id === $user->id;
    }
}
