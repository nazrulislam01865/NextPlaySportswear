<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkQuoteActivity extends Model
{
    protected $fillable = [
        'bulk_quote_request_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'note',
        'metadata',
        'occurred_at',
    ];

    public function bulkQuote(): BelongsTo
    {
        return $this->belongsTo(BulkQuoteRequest::class, 'bulk_quote_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
