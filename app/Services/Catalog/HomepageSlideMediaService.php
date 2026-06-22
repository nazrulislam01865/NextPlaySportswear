<?php

namespace App\Services\Catalog;

use App\Models\HomepageSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSlideMediaService
{
    public function sync(HomepageSlide $slide, Request $request): void
    {
        $uploaded = $request->file('image_file');
        $imageUrl = trim((string) $request->input('image_url', ''));

        if ($request->boolean('remove_image')) {
            $this->deletePath($slide->image_path);
            $slide->image_path = null;
            $slide->image_url = null;
        }

        if ($uploaded) {
            $this->deletePath($slide->image_path);
            $slide->image_path = $uploaded->store("homepage/slides/{$slide->id}", 'public');
            $slide->image_url = null;
        } elseif ($imageUrl !== '') {
            $this->deletePath($slide->image_path);
            $slide->image_path = null;
            $slide->image_url = $imageUrl;
        }

        $slide->save();
    }

    public function deleteAll(HomepageSlide $slide): void
    {
        $this->deletePath($slide->image_path);
    }

    private function deletePath(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
