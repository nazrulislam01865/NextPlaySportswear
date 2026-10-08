<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
        'country_name',
        'year',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dates(): HasMany
    {
        return $this->hasMany(HolidayCalendarDate::class)->orderBy('date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCountry(Builder $query, string $country): Builder
    {
        $normalized = strtoupper(trim($country));

        return $query->where(function (Builder $q) use ($normalized, $country): void {
            $q->where('country_code', $normalized)
                ->orWhere('country_name', $country)
                ->orWhereRaw('LOWER(country_name) = ?', [strtolower(trim($country))]);
        });
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function flagEmoji(): string
    {
        $iso = strtoupper(trim((string) $this->country_code));

        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            return '🏳️';
        }

        return mb_chr(127397 + ord($iso[0]), 'UTF-8').mb_chr(127397 + ord($iso[1]), 'UTF-8');
    }
}
