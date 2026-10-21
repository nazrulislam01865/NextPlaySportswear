<?php

namespace App\Services\Catalog;

use App\Models\HomepageSlide;
use Illuminate\Http\Request;

class HomepageSlideMediaService
{
    public function sync(HomepageSlide $slide, Request $request): void
    {
        $uploaded = $request->file('image_file');
        $imageUrl = trim((string) $request->input('image_url', ''));
        $mobileUploaded = $request->file('mobile_image_file');
        $mobileImageUrl = trim((string) $request->input('mobile_image_url', ''));

        if ($uploaded) {
            $slide->image_path = StoredPublicImage::replace($uploaded, "homepage/slides/{$slide->id}", $slide->image_path);
            $slide->image_url = null;
        } elseif ($request->boolean('remove_image')) {
            StoredPublicImage::retire($slide->image_path);
            $slide->image_path = null;
            $slide->image_url = null;
        } elseif ($imageUrl !== '') {
            StoredPublicImage::retire($slide->image_path);
            $slide->image_path = null;
            $slide->image_url = $imageUrl;
        }

        if ($mobileUploaded) {
            $slide->mobile_image_path = StoredPublicImage::replace($mobileUploaded, "homepage/slides/{$slide->id}/mobile", $slide->mobile_image_path);
            $slide->mobile_image_url = null;
        } elseif ($request->boolean('remove_mobile_image')) {
            StoredPublicImage::retire($slide->mobile_image_path);
            $slide->mobile_image_path = null;
            $slide->mobile_image_url = null;
        } elseif ($mobileImageUrl !== '') {
            StoredPublicImage::retire($slide->mobile_image_path);
            $slide->mobile_image_path = null;
            $slide->mobile_image_url = $mobileImageUrl;
        }

        $slide->save();
    }

    public function deleteAll(HomepageSlide $slide): void
    {
        StoredPublicImage::retire($slide->image_path);
        StoredPublicImage::retire($slide->mobile_image_path);
    }

}
