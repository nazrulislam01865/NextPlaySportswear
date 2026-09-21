<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontSetting extends Model
{
    protected $fillable = [
        'key',
        'settings',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
