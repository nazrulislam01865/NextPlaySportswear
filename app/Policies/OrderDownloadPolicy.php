<?php

namespace App\Policies;

use App\Models\OrderDownload;
use App\Models\User;

class OrderDownloadPolicy
{
    public function download(User $user, OrderDownload $download): bool
    {
        return $user->isCustomer()
            && $download->order?->user_id === $user->id
            && $download->isAvailable();
    }
}
