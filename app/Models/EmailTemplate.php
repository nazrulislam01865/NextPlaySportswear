<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'name',
    'description',
    'trigger_event',
    'icon',
    'status',
    'active_version',
    'draft_version',
    'subject',
    'preheader_text',
    'heading',
    'intro_message',
    'cta_label',
    'cta_url_type',
    'cta_custom_url',
    'visibility_settings',
    'blocks',
    'sample_data',
    'published_at',
    'published_by',
    'updated_by',
])]
class EmailTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'visibility_settings' => 'array',
            'blocks' => 'array',
            'sample_data' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class)->orderByDesc('id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return strtolower((string) $this->status) === 'published';
    }

    public function nextVersion(): string
    {
        $current = (string) ($this->active_version ?? 'v1.0');
        if (preg_match('/^v?(\d+)\.(\d+)$/', $current, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2] + 1;
            return "v{$major}.{$minor}";
        }

        return 'v1.1';
    }

    public function createVersionSnapshot(string $version, ?int $userId = null): EmailTemplateVersion
    {
        return $this->versions()->create([
            'version' => $version,
            'status' => 'published',
            'subject' => $this->subject,
            'preheader_text' => $this->preheader_text,
            'heading' => $this->heading,
            'intro_message' => $this->intro_message,
            'cta_label' => $this->cta_label,
            'cta_url_type' => $this->cta_url_type,
            'cta_custom_url' => $this->cta_custom_url,
            'visibility_settings' => $this->visibility_settings,
            'blocks' => $this->blocks,
            'created_by' => $userId,
        ]);
    }
}
