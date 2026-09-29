<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SustainabilityPageSetting extends Model
{
    protected $fillable = ['hero', 'approach', 'features', 'informed', 'cta', 'seo', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'hero' => 'array', 'approach' => 'array', 'features' => 'array',
            'informed' => 'array', 'cta' => 'array', 'seo' => 'array',
        ];
    }
}
