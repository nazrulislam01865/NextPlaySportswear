<?php

namespace App\Services\Catalog;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        if ((string) $section->key === 'hero') {
            $this->syncHeroSlides($section, $request);
        }

        $section->save();
    }

    private function syncHeroSlides(HomepageSection $section, Request $request): void
    {
        $existingSlides = collect(is_array($section->hero_slides) ? $section->hero_slides : [])
            ->filter(fn ($slide): bool => is_array($slide))
            ->mapWithKeys(function (array $slide, int $index): array {
                $id = trim((string) ($slide['id'] ?? '')) ?: 'stored-'.($index + 1);

                return [$id => $slide];
            });

        $submittedRows = $request->input('hero_slides', []);
        $submittedRows = is_array($submittedRows) ? array_values($submittedRows) : [];
        $savedSlides = [];
        $seenIds = [];

        foreach ($submittedRows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || isset($seenIds[$id])) {
                $id = (string) Str::uuid();
            }
            $seenIds[$id] = true;

            $existing = $existingSlides->get($id, []);
            $existingPath = trim((string) ($existing['image_path'] ?? '')) ?: null;
            $existingUrl = trim((string) ($existing['image_url'] ?? $existing['image'] ?? '')) ?: null;
            $uploaded = $request->file("hero_slides.{$index}.image_file");
            $submittedUrl = trim((string) ($row['image_url'] ?? '')) ?: null;
            $imagePath = $existingPath;
            $imageUrl = $existingUrl;

            if ($uploaded) {
                $this->deletePath($existingPath);
                $imagePath = $uploaded->store('homepage/sections/hero/slides', 'public');
                $imageUrl = null;
            } elseif ($submittedUrl !== null) {
                if ($submittedUrl !== $existingUrl) {
                    $this->deletePath($existingPath);
                    $imagePath = null;
                }
                $imageUrl = $submittedUrl;
            } elseif ($existingPath === null) {
                $submittedPath = trim((string) ($row['image_path'] ?? ''));
                if ($submittedPath !== '') {
                    $imagePath = $submittedPath;
                }
            }

            if ($imagePath === null && $imageUrl === null) {
                continue;
            }

            $savedSlides[] = [
                'id' => $id,
                'image_path' => $imagePath,
                'image_url' => $imageUrl,
                'image_alt' => trim(strip_tags((string) ($row['image_alt'] ?? $existing['image_alt'] ?? ''))) ?: 'Custom team sportswear',
            ];
        }

        $existingSlides
            ->reject(fn (array $slide, string $id): bool => isset($seenIds[$id]))
            ->each(fn (array $slide) => $this->deletePath(trim((string) ($slide['image_path'] ?? '')) ?: null));

        $section->hero_slides = array_values($savedSlides);
    }

    private function deletePath(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
