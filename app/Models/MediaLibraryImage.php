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
        'original_filename',
        'category',
        'caption',
        'description',
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

    public function type(): string
    {
        $mime = strtolower((string) $this->mime_type);

        // Imported product images predate MIME capture.
        if ($mime === '' || str_starts_with($mime, 'image/')) {
            return 'images';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'videos';
        }
        if (in_array($mime, [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ], true)) {
            return 'documents';
        }

        return 'others';
    }

    public function filename(): string
    {
        if (filled($this->original_filename)) {
            return basename(str_replace('\\', '/', (string) $this->original_filename));
        }
        $name = trim((string) $this->name) ?: 'Media file';
        $extension = strtolower((string) pathinfo((string) $this->path, PATHINFO_EXTENSION));

        return $extension !== '' && ! str_ends_with(strtolower($name), '.'.$extension)
            ? $name.'.'.$extension : $name;
    }

    public function productImages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class, 'media_library_image_id');
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
