<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'product_type', 'brand',
        'badge_label', 'badge_color', 'short_description', 'description_html', 'features',
        'specifications', 'base_price', 'compare_at_price', 'cost_price', 'currency',
        'minimum_quantity', 'maximum_quantity', 'is_featured', 'is_customizable', 'is_active',
        'track_inventory', 'stock_quantity', 'low_stock_threshold', 'allow_backorder', 'weight',
        'dimensions', 'shipping_class', 'tax_class', 'tags', 'price_table_headers', 'price_table_rows',
        'price_table_highlight_column', 'price_table_note', 'meta_title', 'meta_description',
        'meta_keywords', 'canonical_url', 'og_title', 'og_description', 'og_image_url',
        'robots_index', 'robots_follow', 'schema_json', 'sort_order', 'published_at',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'specifications' => 'array',
            'dimensions' => 'array',
            'tags' => 'array',
            'price_table_headers' => 'array',
            'price_table_rows' => 'array',
            'schema_json' => 'array',
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'is_featured' => 'boolean',
            'is_customizable' => 'boolean',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            'allow_backorder' => 'boolean',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('is_active', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'is_featured', 'sort_order'])
            ->withTimestamps()
            ->orderBy('category_product.sort_order');
    }

    public function primaryCategory(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', true);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAttributeValue::class,
            'attribute_value_product',
            'product_id',
            'attribute_value_id'
        )->withPivot('sort_order')->with('attribute');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order');
    }

    public function sizeGroups(): HasMany
    {
        return $this->hasMany(ProductSizeGroup::class)->orderBy('sort_order');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('minimum_quantity');
    }

    public function artworkMethods(): HasMany
    {
        return $this->hasMany(ProductArtworkMethod::class)->orderBy('sort_order');
    }

    public function productionSpeeds(): HasMany
    {
        return $this->hasMany(ProductProductionSpeed::class)->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    public function primaryImageUrl(): string
    {
        $image = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        if (! $image) {
            return asset('images/product-placeholder.svg');
        }

        return $image->publicUrl();
    }
}
