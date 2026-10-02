<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'progress_amount', 'progress_target_override', 'reward_amount_override', 'updated_by'])]
class CustomerRewardProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'progress_amount' => 'decimal:2',
            'progress_target_override' => 'decimal:2',
            'reward_amount_override' => 'decimal:2',
        ];
    }
}
