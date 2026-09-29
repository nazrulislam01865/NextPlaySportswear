<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'hero',
    'introduction',
    'what_we_do',
    'how_we_work',
    'gallery',
    'cta',
    'help',
    'seo',
    'created_by',
    'updated_by',
])]
class AboutPageSetting extends Model
{
    protected function casts(): array
    {
        return [
            'hero' => 'array',
            'introduction' => 'array',
            'what_we_do' => 'array',
            'how_we_work' => 'array',
            'gallery' => 'array',
            'cta' => 'array',
            'help' => 'array',
            'seo' => 'array',
        ];
    }
}
