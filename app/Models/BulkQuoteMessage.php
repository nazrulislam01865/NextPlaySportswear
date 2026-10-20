<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkQuoteMessage extends Model
{
    protected $fillable = ['bulk_quote_request_id', 'actor_id', 'sender', 'kind', 'body', 'change_types'];

    protected function casts(): array
    {
        return ['change_types' => 'array'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(BulkQuoteFile::class, 'bulk_quote_message_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
