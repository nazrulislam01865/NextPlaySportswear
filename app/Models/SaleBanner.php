<?php

namespace App\Models;

use App\Support\PromotionBannerPlacement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleBanner extends Model
{
    use HasFactory;

    public const PLACEMENT_SALE_TOP = PromotionBannerPlacement::SALE_TOP;
    public const PLACEMENT_SALE_MIDDLE = PromotionBannerPlacement::SALE_MIDDLE;
    public const PLACEMENT_ALL_PRODUCTS_TOP = PromotionBannerPlacement::ALL_PRODUCTS_TOP;
    public const PLACEMENT_ALL_PRODUCTS_MIDDLE = PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE;
    public const PLACEMENT_CATEGORY_TOP = PromotionBannerPlacement::CATEGORY_TOP;
    public const PLACEMENTS = PromotionBannerPlacement::ALL;

    protected $fillable = [
        'sale_campaign_id',
        'name',
        'desktop_image_path',
        'mobile_image_path',
        'alt_text',
        'heading',
        'cta_label',
        'destination_link',
        'placements',
        'priority',
        'inherit_campaign_schedule',
        'starts_at',
        'ends_at',
        'timezone',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'placements' => 'array',
            'priority' => 'integer',
            'inherit_campaign_schedule' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SaleCampaign::class, 'sale_campaign_id');
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
