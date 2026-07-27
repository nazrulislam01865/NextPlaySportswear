<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'order_id', 'product_id', 'product_slug', 'product_name', 'sku', 'image_url', 'quantity',
    'fulfilled_quantity', 'cancelled_quantity', 'returned_quantity', 'unit_price',
    'customization_unit_price', 'line_total', 'customization', 'is_digital',
])]
class OrderItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function remainingCancellableQuantity(): int
    {
        return max(0, $this->quantity - $this->fulfilled_quantity - $this->cancelled_quantity);
    }

    public function remainingFulfillableQuantity(): int
    {
        $allocated = (int) $this->shipmentItems()->sum('quantity');

        return max(0, $this->quantity - $this->cancelled_quantity - $allocated);
    }

    /** @return array<int, array<string, mixed>> */
    public function artworkFiles(): array
    {
        $customization = (array) $this->customization;
        $files = collect((array) ($customization['artwork_files'] ?? []))
            ->filter(fn ($file): bool => is_array($file) && filled($file['path'] ?? null))
            ->map(fn (array $file): array => [
                'path' => (string) $file['path'],
                'original_name' => basename((string) ($file['original_name'] ?? 'Artwork file')),
                'size' => max(0, (int) ($file['size'] ?? 0)),
                'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
            ])
            ->values();

        if ($files->isEmpty() && filled($customization['artwork_path'] ?? null)) {
            $files->push([
                'path' => (string) $customization['artwork_path'],
                'original_name' => basename((string) ($customization['artwork_original_name'] ?? 'Artwork file')),
                'size' => 0,
                'mime_type' => 'application/octet-stream',
            ]);
        }

        return $files->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function rosterRows(): array
    {
        return collect((array) data_get($this->customization, 'configuration.roster', []))
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): array => [
                'size_group_label' => (string) ($row['size_group_label'] ?? ''),
                'size_code' => (string) ($row['size_code'] ?? ''),
                'size_label' => (string) ($row['size_label'] ?? $row['size_code'] ?? ''),
                'values' => collect((array) ($row['values'] ?? []))
                    ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{key:string,label:string,type:string}> */
    public function rosterFields(): array
    {
        $snapshotFields = collect((array) data_get($this->customization, 'roster_fields', []))
            ->filter(fn ($field): bool => is_array($field) && filled($field['key'] ?? null))
            ->map(fn (array $field): array => [
                'key' => (string) $field['key'],
                'label' => (string) ($field['label'] ?? Str::headline((string) $field['key'])),
                'type' => (string) ($field['type'] ?? 'text'),
            ])
            ->unique('key')
            ->values();

        if ($snapshotFields->isNotEmpty()) {
            return $snapshotFields->all();
        }

        return collect($this->rosterRows())
            ->flatMap(fn (array $row): array => array_keys((array) ($row['values'] ?? [])))
            ->filter()
            ->unique()
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => Str::headline($key),
                'type' => 'text',
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{group_label:string,size_code:string,size_label:string,quantity:int}> */
    public function selectedSizes(): array
    {
        $snapshot = collect((array) data_get($this->customization, 'size_breakdown', []))
            ->filter(fn ($line): bool => is_array($line) && (int) ($line['quantity'] ?? 0) > 0)
            ->map(fn (array $line): array => [
                'group_label' => (string) ($line['group_label'] ?? 'Sizes'),
                'size_code' => (string) ($line['size_code'] ?? $line['size_label'] ?? ''),
                'size_label' => (string) ($line['size_label'] ?? $line['size_code'] ?? 'Size'),
                'quantity' => max(1, (int) $line['quantity']),
            ])
            ->values();

        if ($snapshot->isNotEmpty()) {
            return $snapshot->all();
        }

        $rosterSizes = collect($this->rosterRows())
            ->groupBy(fn (array $row): string => implode('|', [
                $row['size_group_label'] ?: 'Sizes',
                $row['size_code'] ?: $row['size_label'],
                $row['size_label'] ?: $row['size_code'],
            ]))
            ->map(function ($rows, string $key): array {
                [$groupLabel, $sizeCode, $sizeLabel] = array_pad(explode('|', $key, 3), 3, '');

                return [
                    'group_label' => $groupLabel ?: 'Sizes',
                    'size_code' => $sizeCode,
                    'size_label' => $sizeLabel ?: $sizeCode ?: 'Size',
                    'quantity' => $rows->count(),
                ];
            })
            ->values();

        if ($rosterSizes->isNotEmpty()) {
            return $rosterSizes->all();
        }

        return collect((array) data_get($this->customization, 'configuration.quantities', []))
            ->filter(fn ($quantity): bool => (int) $quantity > 0)
            ->map(function ($quantity, $key): array {
                $sizeCode = Str::afterLast((string) $key, ':');

                return [
                    'group_label' => 'Sizes',
                    'size_code' => $sizeCode,
                    'size_label' => $sizeCode !== '' ? $sizeCode : 'Size',
                    'quantity' => (int) $quantity,
                ];
            })
            ->values()
            ->all();
    }

    public function returnableQuantity(): int
    {
        $activeStatuses = ['requested', 'under_review', 'approved', 'label_issued', 'in_transit'];

        if ($this->relationLoaded('returnItems')) {
            $reserved = (int) $this->returnItems
                ->filter(fn (OrderReturnItem $returnItem): bool => in_array(
                    $returnItem->returnRequest?->status,
                    $activeStatuses,
                    true,
                ))
                ->sum('quantity');
        } else {
            $reserved = (int) $this->returnItems()
                ->whereHas('returnRequest', fn ($query) => $query->whereIn('status', $activeStatuses))
                ->sum('quantity');
        }

        return max(0, $this->fulfilled_quantity - $this->returned_quantity - $reserved);
    }

    protected function casts(): array
    {
        return [
            'customization' => 'array',
            'is_digital' => 'boolean',
            'unit_price' => 'decimal:2',
            'customization_unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
