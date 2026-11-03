<?php

namespace App\Policies;

use App\Models\OrderDownload;
use App\Models\User;

class OrderDownloadPolicy
{
    public function download(User $user, OrderDownload $download): bool
    {
        // Authorization determines ownership only. The controller checks
        // expiry and download limits again inside a row-locked transaction,
        // returning 410 Gone for an exhausted or expired owner's download.
        return $user->isCustomer()
            && $download->order?->user_id === $user->id;
    }
}
