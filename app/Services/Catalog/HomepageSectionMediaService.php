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

        $section->save();
    }

    private function deletePath(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
