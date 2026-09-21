<?php

namespace App\Services\Catalog;

use App\Models\HomepageSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSlideMediaService
{
    public function __construct(private readonly HomepageStagedUploadService $stagedUploads)
    {
    }

    public function sync(HomepageSlide $slide, Request $request): void
    {
        $uploaded = $request->file('image_file');
        $uploadToken = trim((string) $request->input('image_upload_token', ''));
        $imageUrl = trim((string) $request->input('image_url', ''));

        if ($request->boolean('remove_image')) {
            $this->deletePath($slide->image_path);
            $slide->image_path = null;
            $slide->image_url = null;
        }

        if ($uploadToken !== '') {
            $newPath = $this->stagedUploads->consumeToPublic(
                (int) $request->user()->id,
                $uploadToken,
                "homepage/slides/{$slide->id}",
            );
            $this->deletePath($slide->image_path);
            $slide->image_path = $newPath;
            $slide->image_url = null;
        } elseif ($uploaded) {
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
        $this->deletePath($slide->mobile_image_path);
    }

    private function deletePath(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
