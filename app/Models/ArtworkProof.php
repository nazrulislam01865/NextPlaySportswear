<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'order_id', 'order_item_id', 'flowtrack_job_id', 'flowtrack_slot', 'flowtrack_document_id', 'flowtrack_revision_id', 'version', 'status', 'design_name', 'file_path',
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
            ->selectRaw('order_item_id, COALESCE(flowtrack_slot, 0) AS proof_slot, MAX(version) AS latest_version')
            ->groupBy('order_item_id')->groupByRaw('COALESCE(flowtrack_slot, 0)');

        return $query->joinSub($latest, 'current_versions', function ($join): void {
            $join->on('artwork_proofs.order_item_id', '=', 'current_versions.order_item_id')
                ->on(DB::raw('COALESCE(artwork_proofs.flowtrack_slot, 0)'), '=', 'current_versions.proof_slot')
                ->on('artwork_proofs.version', '=', 'current_versions.latest_version');
        })->where(function (Builder $visible): void {
            // An externally delivered proof supersedes the old NPS-admin
            // proof for that item without deleting its history.
            $visible->whereNotNull('artwork_proofs.flowtrack_slot')
                ->orWhereNotExists(function ($query): void {
                    $query->selectRaw('1')->from('artwork_proofs as source_proofs')
                        ->whereColumn('source_proofs.order_item_id', 'artwork_proofs.order_item_id')
                        ->whereNotNull('source_proofs.flowtrack_slot');
                });
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
            'flowtrack_job_id' => 'integer',
            'flowtrack_slot' => 'integer',
            'flowtrack_document_id' => 'integer',
            'flowtrack_revision_id' => 'integer',
            'file_size' => 'integer',
            'change_types' => 'array',
            'approved_at' => 'datetime',
            'revision_requested_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
