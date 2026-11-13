<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function topics(): array
    {
        return [
            'product-question' => 'Product Question',
            'order-support' => 'Order Support',
            'customization' => 'Customization',
            'bulk-quote' => 'Bulk Quote',
            'shipping' => 'Shipping',
            'return-refund' => 'Returns & Refunds',
            'website-help' => 'Website Help',
            'other' => 'Other',
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'topic',
        'order_number',
        'message',
        'internal_note',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
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
            // Support messages can contain order details, so encrypt the body at rest.
            'message' => 'encrypted',
            'internal_note' => 'encrypted',
        ];
    }
}
