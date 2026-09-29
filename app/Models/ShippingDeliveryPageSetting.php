<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'hero',
    'tabs',
    'delivery_intro',
    'info_cards',
    'delivery_steps',
    'notice',
    'faqs',
    'address_checklist',
    'cta',
    'seo',
    'created_by',
    'updated_by',
])]
class ShippingDeliveryPageSetting extends Model
{
    protected function casts(): array
    {
        return [
            'hero' => 'array',
            'tabs' => 'array',
            'delivery_intro' => 'array',
            'info_cards' => 'array',
            'delivery_steps' => 'array',
            'notice' => 'array',
            'faqs' => 'array',
            'address_checklist' => 'array',
            'cta' => 'array',
            'seo' => 'array',
        ];
    }
}
