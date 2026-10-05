<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryCallingCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_name',
        'iso_code',
        'dial_code',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('country_name')->orderBy('id');
    }

    public function flagEmoji(): string
    {
        $iso = strtoupper(trim((string) $this->iso_code));

        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            return '';
        }

        return mb_chr(127397 + ord($iso[0]), 'UTF-8').mb_chr(127397 + ord($iso[1]), 'UTF-8');
    }
}
