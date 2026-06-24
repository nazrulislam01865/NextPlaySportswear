<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\PublicMedia;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_option_group_id', 'jersey_customization_option_id', 'label', 'code', 'description', 'color_hex', 'image_path',
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


    public function jerseyCustomizationOption(): BelongsTo
    {
        return $this->belongsTo(JerseyCustomizationOption::class, 'jersey_customization_option_id');
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

                $url = PublicMedia::url(
                    filled($image['path'] ?? null) ? (string) $image['path'] : null,
                    filled($image['url'] ?? null) ? (string) $image['url'] : null
                );

                return $url ? ['url' => $url, 'alt' => $image['alt'] ?? $this->label] : null;
            })
            ->filter()
            ->values();

        if ($images->isEmpty()) {
            $url = PublicMedia::url($this->image_path, $this->image_url);
            if ($url) {
                $images->push(['url' => $url, 'alt' => $this->label]);
            }
        }

        return $images->all();
    }
}
