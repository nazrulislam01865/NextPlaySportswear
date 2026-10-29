<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryRegion extends Model
{
    protected $fillable = ['country_calling_code_id', 'name', 'code', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(CountryCallingCode::class, 'country_calling_code_id');
    }
}
