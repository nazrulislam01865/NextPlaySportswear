<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryMediaService
{
    private const FIELDS = [
        'image' => ['path' => 'image_path', 'url' => 'image_url'],
        'thumbnail' => ['path' => 'thumbnail_path', 'url' => 'thumbnail_url'],
        'icon' => ['path' => 'icon_path', 'url' => 'icon_url'],
        'banner' => ['path' => 'banner_path', 'url' => 'banner_url'],
        'mobile_banner' => ['path' => 'mobile_banner_path', 'url' => 'mobile_banner_url'],
        'og_image' => ['path' => 'og_image_path', 'url' => 'og_image_url'],
    ];

    public function sync(Category $category, Request $request): void
    {
        foreach (self::FIELDS as $input => $columns) {
            $pathColumn = $columns['path'];
            $urlColumn = $columns['url'];

            if ($input === 'icon' && $category->parent_id !== null) {
                StoredPublicImage::retire($category->{$pathColumn});
                $category->{$pathColumn} = null;
                $category->{$urlColumn} = null;
                continue;
            }

            $uploaded = $request->file($input.'_file');
            $url = trim((string) $request->input($input.'_url', ''));
            $remove = $request->boolean('remove_'.$input);

            // A newly selected upload takes precedence over a stale remove checkbox.
            if ($uploaded) {
                $category->{$pathColumn} = StoredPublicImage::replace(
                    $uploaded,
                    "categories/{$category->id}",
                    $category->{$pathColumn},
                );
                $category->{$urlColumn} = $urlColumn === 'image_url' ? '' : null;
                continue;
            }

            if ($remove) {
                StoredPublicImage::retire($category->{$pathColumn});
                $category->{$pathColumn} = null;
                $category->{$urlColumn} = $urlColumn === 'image_url' ? '' : null;
                continue;
            }

            if ($url !== '') {
                StoredPublicImage::retire($category->{$pathColumn});
                $category->{$pathColumn} = null;
                $category->{$urlColumn} = $url;
            } elseif (! $category->exists && $urlColumn === 'image_url') {
                $category->{$urlColumn} = '';
            }
        }

        $category->save();
    }


    public function duplicate(Category $source, Category $target): void
    {
        foreach (self::FIELDS as $columns) {
            $pathColumn = $columns['path'];
            $urlColumn = $columns['url'];
            $target->{$urlColumn} = $source->{$urlColumn};
            $target->{$pathColumn} = $this->copyPath($source->{$pathColumn}, "categories/{$target->id}");
        }

        $target->save();
    }

    public function copyPath(?string $sourcePath, string $targetDirectory): ?string
    {
        if (! filled($sourcePath) || ! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $filename = (string) \Illuminate\Support\Str::uuid().($extension !== '' ? '.'.$extension : '');
        $targetPath = trim($targetDirectory, '/').'/'.$filename;

        return Storage::disk('public')->copy($sourcePath, $targetPath) ? $targetPath : null;
    }

    public function deleteAll(Category $category): void
    {
        foreach (self::FIELDS as $columns) {
            StoredPublicImage::retire($category->{$columns['path']});
        }

        $category->contentBlocks()
            ->pluck('image_path')
            ->filter()
            ->each(fn (string $path) => StoredPublicImage::retire($path));

        StoredPublicImage::retireDirectory("categories/{$category->id}");
    }

}
