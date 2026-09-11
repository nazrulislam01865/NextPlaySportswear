<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'carrier',
    'country',
    'iata_code',
    'state',
    'postal_code_low',
    'postal_code_high',
    'postal_code_low_normalized',
    'postal_code_high_normalized',
    'postal_code_low_numeric',
    'postal_code_high_numeric',
    'postal_code_patterns',
    'city',
    'city_normalized',
    'origin_surcharge',
    'destination_surcharge',
    'amount',
    'extra_charge',
    'is_active',
    'source_file',
    'source_row',
    'source_hash',
    'import_batch_id',
])]
class RuralAreaSurcharge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'extra_charge' => 'decimal:2',
            'postal_code_low_numeric' => 'integer',
            'postal_code_high_numeric' => 'integer',
            'source_row' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function patternList(): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $this->postal_code_patterns))
            ->map(fn ($pattern): string => trim((string) $pattern))
            ->filter()
            ->values()
            ->all();
    }

    public function effectiveExtraCharge(): float
    {
        return round((float) ($this->extra_charge ?? $this->amount ?? 0), 2);
    }

    public function isStructuredRemoteAreaRecord(): bool
    {
        return filled($this->iata_code) && filled($this->postal_code_low) && filled($this->postal_code_high);
    }

    public function postalRangeLabel(): string
    {
        if (! $this->isStructuredRemoteAreaRecord()) {
            return implode(', ', array_slice($this->patternList(), 0, 3));
        }

        if ((string) $this->postal_code_low === (string) $this->postal_code_high) {
            return (string) $this->postal_code_low;
        }

        return $this->postal_code_low.' - '.$this->postal_code_high;
    }
}
