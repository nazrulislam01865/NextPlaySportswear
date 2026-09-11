<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id',
    'created_by',
    'carrier',
    'source_file',
    'extra_charge',
    'replace_existing',
    'expected_rows',
    'rows_received',
    'status',
    'error_message',
])]
class RuralAreaSurchargeImportBatch extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'extra_charge' => 'decimal:2',
            'replace_existing' => 'boolean',
            'expected_rows' => 'integer',
            'rows_received' => 'integer',
        ];
    }
}
