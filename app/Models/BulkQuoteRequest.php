<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkQuoteRequest extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    protected $fillable = [
        'reference',
        'full_name',
        'organization',
        'email',
        'phone',
        'product_type',
        'estimated_quantity',
        'sizes_needed',
        'budget_range',
        'artwork_details',
        'customization_types',
        'shipping_address',
        'country',
        'state_province',
        'postal_code',
        'preferred_shipping_method',
        'needed_by',
        'event_date',
        'attachment',
        'additional_notes',
        'status',
        'ip_hash',
        'user_agent_hash',
    ];

    protected $hidden = [
        'ip_hash',
        'user_agent_hash',
    ];

    protected function casts(): array
    {
        return [
            'customization_types' => 'array',
            'attachment' => 'array',
            'needed_by' => 'date',
            'event_date' => 'date',
        ];
    }
}
