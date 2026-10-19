<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

#[Fillable([
    'order_id', 'order_item_id', 'version', 'status', 'design_name', 'file_path',
    'original_name', 'mime_type', 'file_size', 'designer_notes', 'customer_feedback',
    'change_types', 'priority', 'reference_path', 'reference_name', 'reference_mime_type',
    'approved_at', 'revision_requested_at', 'rejected_at',
])]
class ArtworkProof extends Model
{
    public const PENDING = 'pending_review';
    public const APPROVED = 'approved';
    public const REVISION = 'needs_revision';
    public const REJECTED = 'rejected';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ArtworkProofMessage::class)->oldest();
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $latest = static::query()
            ->selectRaw('order_item_id, MAX(version) AS latest_version')
            ->groupBy('order_item_id');

        return $query->joinSub($latest, 'current_versions', function ($join): void {
            $join->on('artwork_proofs.order_item_id', '=', 'current_versions.order_item_id')
                ->on('artwork_proofs.version', '=', 'current_versions.latest_version');
        })->select('artwork_proofs.*');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REVISION => 'Needs Revision',
            self::REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    public function quantityLabel(): string
    {
        $item = $this->orderItem;
        return $item->quantity.' '.(stripos($item->product_name, 'jersey') !== false ? 'Jerseys' : 'Items');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'file_size' => 'integer',
            'change_types' => 'array',
            'approved_at' => 'datetime',
            'revision_requested_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
