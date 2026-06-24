<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_option_group_id', 'label', 'code', 'description', 'color_hex', 'image_path',
        'image_url', 'image_gallery', 'price_adjustment', 'charge_type', 'stock_quantity',
        'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'image_gallery' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_id');
    }

    public function publicImageUrl(): ?string
    {
        return $this->publicImages()[0]['url'] ?? null;
    }

    /** @return array<int, array{url:string, alt:?string}> */
    public function publicImages(): array
    {
        $images = collect($this->image_gallery ?? [])
            ->map(function ($image): ?array {
                if (! is_array($image)) {
                    return null;
                }

                $url = filled($image['url'] ?? null)
                    ? (string) $image['url']
                    : (filled($image['path'] ?? null) ? Storage::disk('public')->url((string) $image['path']) : null);

                return $url ? ['url' => $url, 'alt' => $image['alt'] ?? $this->label] : null;
            })
            ->filter()
            ->values();

        if ($images->isEmpty()) {
            if ($this->image_url) {
                $images->push(['url' => $this->image_url, 'alt' => $this->label]);
            } elseif ($this->image_path) {
                $images->push(['url' => Storage::disk('public')->url($this->image_path), 'alt' => $this->label]);
            }
        }

        return $images->all();
    }
}
