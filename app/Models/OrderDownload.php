<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'order_item_id',
    'token',
    'title',
    'file_path',
    'original_name',
    'mime_type',
    'file_size',
    'download_limit',
    'download_count',
    'expires_at',
    'is_active',
    'license_note',
])]
class OrderDownload extends Model
{
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && (! $this->expires_at || $this->expires_at->isFuture())
            && ($this->download_limit === null || $this->download_count < $this->download_limit);
    }

    public function remainingDownloads(): ?int
    {
        return $this->download_limit === null
            ? null
            : max(0, $this->download_limit - $this->download_count);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
