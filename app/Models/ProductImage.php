<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\PublicMedia;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'url', 'alt_text', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function publicUrl(): string
    {
        return PublicMedia::url(
            $this->path,
            $this->url,
            '/images/product-placeholder.svg'
        );
    }
}
