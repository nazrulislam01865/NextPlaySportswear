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
        $mobileUploaded = $request->file('mobile_image_file');
        $mobileImageUrl = trim((string) $request->input('mobile_image_url', ''));

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

        if ($request->boolean('remove_mobile_image')) {
            $this->deletePath($slide->mobile_image_path);
            $slide->mobile_image_path = null;
            $slide->mobile_image_url = null;
        }

        if ($mobileUploaded) {
            $this->deletePath($slide->mobile_image_path);
            $slide->mobile_image_path = $mobileUploaded->store("homepage/slides/{$slide->id}/mobile", 'public');
            $slide->mobile_image_url = null;
        } elseif ($mobileImageUrl !== '') {
            $this->deletePath($slide->mobile_image_path);
            $slide->mobile_image_path = null;
            $slide->mobile_image_url = $mobileImageUrl;
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
