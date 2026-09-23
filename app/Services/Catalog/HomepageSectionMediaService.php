<?php

namespace App\Services\Catalog;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomepageSectionMediaService
{
    /**
     * @param array<int, array<string, mixed>> $originalItems
     */
    public function sync(HomepageSection $section, Request $request, array $originalItems = []): void
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

        if ((string) $section->key === 'process') {
            $this->syncItemImages($section, $request, $originalItems);
        }

        $section->save();
    }

    /**
     * Keep stored item media attached to the same stable item id while text is edited
     * or the process steps are reordered in the admin UI.
     *
     * @param array<int, array<string, mixed>> $existingItems
     * @param array<int, array<string, mixed>> $submittedItems
     * @return array<int, array<string, mixed>>
     */
    public function preserveItemMedia(string $sectionKey, array $existingItems, array $submittedItems): array
    {
        $existingById = collect($existingItems)
            ->filter(fn ($item): bool => is_array($item))
            ->mapWithKeys(function (array $item, int $index) use ($sectionKey): array {
                $id = trim((string) ($item['id'] ?? '')) ?: $sectionKey.'-item-'.($index + 1);
                return [$id => $item];
            });

        return collect($submittedItems)
            ->filter(fn ($item): bool => is_array($item))
            ->values()
            ->map(function (array $item, int $index) use ($sectionKey, $existingById): array {
                $id = trim((string) ($item['id'] ?? '')) ?: $sectionKey.'-item-'.($index + 1);
                $existing = $existingById->get($id, []);
                $item['id'] = $id;

                foreach (['image_path', 'image_url'] as $field) {
                    $value = trim((string) ($existing[$field] ?? ''));
                    if ($value !== '' && blank($item[$field] ?? null)) {
                        $item[$field] = $value;
                    }
                }

                if (blank($item['image_alt'] ?? null) && filled($existing['image_alt'] ?? null)) {
                    $item['image_alt'] = trim((string) $existing['image_alt']);
                }

                return $item;
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $originalItems
     */
    private function syncItemImages(HomepageSection $section, Request $request, array $originalItems): void
    {
        $items = collect(is_array($section->items) ? $section->items : [])
            ->filter(fn ($item): bool => is_array($item))
            ->values()
            ->all();

        foreach ($items as $index => &$item) {
            $uploaded = $request->file("items.{$index}.image_file");
            if (! $uploaded) {
                continue;
            }

            $existingPath = trim((string) ($item['image_path'] ?? '')) ?: null;
            $this->deletePath($existingPath);

            $item['image_path'] = $uploaded->store("homepage/sections/{$section->key}/items", 'public');
            unset($item['image_url']);
        }
        unset($item);

        $referencedPaths = collect($items)
            ->pluck('image_path')
            ->filter(fn ($path): bool => filled($path))
            ->map(fn ($path): string => (string) $path)
            ->all();

        collect($originalItems)
            ->filter(fn ($item): bool => is_array($item))
            ->pluck('image_path')
            ->filter(fn ($path): bool => filled($path) && ! in_array((string) $path, $referencedPaths, true))
            ->each(fn ($path) => $this->deletePath((string) $path));

        $section->items = array_values($items);
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
