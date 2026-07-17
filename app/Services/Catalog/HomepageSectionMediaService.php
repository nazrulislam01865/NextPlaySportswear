<?php

namespace App\Services\Catalog;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSectionMediaService
{
    public function sync(HomepageSection $section, Request $request): void
    {
        $uploaded = $request->file('image_file');
        $imageUrl = trim((string) $request->input('image_url', ''));
        $mobileUploaded = $request->file('mobile_image_file');
        $mobileImageUrl = trim((string) $request->input('mobile_image_url', ''));

        if ($request->boolean('remove_image')) {
            $this->deletePath($section->image_path);
            $section->image_path = null;
            $section->image_url = null;
        }

        if ($uploaded) {
            $this->deletePath($section->image_path);
            $section->image_path = $uploaded->store("homepage/sections/{$section->key}", 'public');
            $section->image_url = null;
        } elseif ($imageUrl !== '') {
            $this->deletePath($section->image_path);
            $section->image_path = null;
            $section->image_url = $imageUrl;
        }

        if ($request->boolean('remove_mobile_image')) {
            $this->deletePath($section->mobile_image_path);
            $section->mobile_image_path = null;
            $section->mobile_image_url = null;
        }

        if ($mobileUploaded) {
            $this->deletePath($section->mobile_image_path);
            $section->mobile_image_path = $mobileUploaded->store("homepage/sections/{$section->key}/mobile", 'public');
            $section->mobile_image_url = null;
        } elseif ($mobileImageUrl !== '') {
            $this->deletePath($section->mobile_image_path);
            $section->mobile_image_path = null;
            $section->mobile_image_url = $mobileImageUrl;
        }

        $section->save();
    }

    private function deletePath(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
