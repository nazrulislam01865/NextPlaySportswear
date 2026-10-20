<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkQuoteFile extends Model
{
    protected $fillable = ['bulk_quote_request_id', 'bulk_quote_message_id', 'uploaded_by', 'version', 'category', 'path', 'original_name', 'mime_type', 'size'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(BulkQuoteRequest::class, 'bulk_quote_request_id');
    }
}
