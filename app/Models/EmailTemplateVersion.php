<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'email_template_id',
    'version',
    'status',
    'subject',
    'preheader_text',
    'heading',
    'intro_message',
    'cta_label',
    'cta_url_type',
    'cta_custom_url',
    'visibility_settings',
    'blocks',
    'created_by',
])]
class EmailTemplateVersion extends Model
{
    protected function casts(): array
    {
        return [
            'visibility_settings' => 'array',
            'blocks' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
