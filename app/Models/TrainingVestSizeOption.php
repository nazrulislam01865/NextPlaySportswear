<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_vest_size_option_group_id', 'label', 'code', 'is_active', 'sort_order'])]
class TrainingVestSizeOption extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TrainingVestSizeOptionGroup::class, 'training_vest_size_option_group_id');
    }
}
