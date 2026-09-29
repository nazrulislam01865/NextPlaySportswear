<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'internal_code',
        'status',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'starts_at',
        'ends_at',
        'timezone',
        'repeat_weekdays',
        'weekdays',
        'applies_to',
        'show_sale_badge',
        'show_sale_page',
        'priority',
        'homepage_slide_id',
        'banner_image_path',
        'banner_mobile_image_path',
        'banner_placements',
        'banner_heading',
        'banner_alt_text',
        'banner_cta_label',
        'banner_destination_link',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'repeat_weekdays' => 'boolean',
            'weekdays' => 'array',
            'show_sale_badge' => 'boolean',
            'show_sale_page' => 'boolean',
            'priority' => 'integer',
            'banner_placements' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'sale_campaign_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'sale_campaign_product');
    }

    public function excludedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'sale_campaign_excluded_product');
    }

    public function homepageSlide(): BelongsTo
    {
        return $this->belongsTo(HomepageSlide::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(SaleBanner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
