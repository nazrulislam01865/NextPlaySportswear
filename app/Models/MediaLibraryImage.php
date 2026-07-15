<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaLibraryImage extends Model
{
    protected $fillable = [
        'path',
        'url',
        'name',
        'alt_text',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publicUrl(): string
    {
        return PublicMedia::url(
            $this->path,
            $this->url,
            '/images/product-placeholder.svg'
        );
    }

    public function sizeLabel(): string
    {
        $bytes = (int) ($this->size_bytes ?? 0);

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
