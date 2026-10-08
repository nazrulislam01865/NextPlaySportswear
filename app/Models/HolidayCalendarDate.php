<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayCalendarDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_calendar_id',
        'date',
        'name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(HolidayCalendar::class, 'holiday_calendar_id');
    }

    public function formattedDate(string $format = 'd/m/Y'): string
    {
        return $this->date instanceof CarbonInterface ? $this->date->format($format) : (string) $this->date;
    }
}
