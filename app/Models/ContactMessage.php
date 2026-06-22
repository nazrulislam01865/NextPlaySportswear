<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'topic',
        'order_number',
        'message',
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
        ];
    }
}
