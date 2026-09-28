<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleBanner extends Model
{
    use HasFactory;

    public const PLACEMENT_SALE_TOP = 'sale_top';
    public const PLACEMENT_PRODUCT_TOP = 'product_top';
    public const PLACEMENT_PRODUCT_AFTER_ROW_2 = 'product_after_row_2';
    public const PLACEMENT_CATEGORY_TOP = 'category_top';

    public const PLACEMENTS = [
        self::PLACEMENT_SALE_TOP,
        self::PLACEMENT_PRODUCT_TOP,
        self::PLACEMENT_PRODUCT_AFTER_ROW_2,
        self::PLACEMENT_CATEGORY_TOP,
    ];

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
