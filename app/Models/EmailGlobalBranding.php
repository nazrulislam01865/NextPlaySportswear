<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'logo_path',
    'header_bg_color',
    'button_color',
    'font_family',
    'footer_text',
    'support_email',
    'support_phone',
    'social_links',
    'is_published',
    'updated_by',
])]
class EmailGlobalBranding extends Model
{
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get or create current active branding settings with fallbacks.
     */
    public static function current(): self
    {
        return self::query()->first() ?? self::query()->create([
            'logo_path' => null,
            'header_bg_color' => '#0B2A4A',
            'button_color' => '#F15A2B',
            'font_family' => 'Inter',
            'footer_text' => "© 2026 NextPlay. All rights reserved.\nPlay More. Live Better.\nYou're receiving this email because you have an account with NextPlay.",
            'support_email' => 'support@nextplay.com',
            'support_phone' => '+1 (888) 123-4567',
            'social_links' => [
                'facebook' => 'https://facebook.com/nextplay',
                'instagram' => 'https://instagram.com/nextplay',
                'twitter' => 'https://twitter.com/nextplay',
                'youtube' => 'https://youtube.com/nextplay',
            ],
            'is_published' => true,
        ]);
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }

        return null;
    }
}
