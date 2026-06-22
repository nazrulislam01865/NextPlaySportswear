<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UrlRedirect extends Model
{
    use HasFactory;

    protected $fillable = ['old_path', 'new_path', 'status_code', 'redirectable_type', 'redirectable_id', 'is_active'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'is_active' => 'boolean'];
    }

    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }
}
