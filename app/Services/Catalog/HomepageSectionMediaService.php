<?php

namespace App\Services\Catalog;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomepageSectionMediaService
{
    /**
     * @param array<int, array<string, mixed>> $originalItems
     */
    public function sync(HomepageSection $section, Request $request, array $originalItems = [], array $originalSettings = []): void
    {
        $uploaded = $request->file('image_file');
        $imageUrl = trim((string) $request->input('image_url', ''));
        $mobileUploaded = $request->file('mobile_image_file');
        $mobileImageUrl = trim((string) $request->input('mobile_image_url', ''));

        if ($uploaded) {
            $section->image_path = StoredPublicImage::replace($uploaded, "homepage/sections/{$section->key}", $section->image_path);
            $section->image_url = null;
        } elseif ($request->boolean('remove_image')) {
            StoredPublicImage::retire($section->image_path);
            $section->image_path = null;
            $section->image_url = null;
        } elseif ($imageUrl !== '') {
            StoredPublicImage::retire($section->image_path);
            $section->image_path = null;
            $section->image_url = $imageUrl;
        }

        if ($mobileUploaded) {
            $section->mobile_image_path = StoredPublicImage::replace($mobileUploaded, "homepage/sections/{$section->key}/mobile", $section->mobile_image_path);
            $section->mobile_image_url = null;
        } elseif ($request->boolean('remove_mobile_image')) {
            StoredPublicImage::retire($section->mobile_image_path);
            $section->mobile_image_path = null;
            $section->mobile_image_url = null;
        } elseif ($mobileImageUrl !== '') {
            StoredPublicImage::retire($section->mobile_image_path);
            $section->mobile_image_path = null;
            $section->mobile_image_url = $mobileImageUrl;
        }

        if ((string) $section->key === 'hero') {
            $this->syncHeroSlides($section, $request);
        }

        if ((string) $section->key === 'process') {
            $this->syncItemImages($section, $request, $originalItems);
        }

        if ((string) $section->key === 'faq') {
            $this->syncQuickHelpIcons($section, $request, $originalSettings);
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
            if ($uploaded) {
                $existingPath = trim((string) ($item['image_path'] ?? '')) ?: null;
                $item['image_path'] = StoredPublicImage::replace($uploaded, "homepage/sections/{$section->key}/items", $existingPath);
                unset($item['image_url']);
            } elseif ($request->boolean("items.{$index}.remove_image")) {
                // Return this card to its bundled prototype image. The original
                // upload is retired by the unused-media check below.
                unset($item['image_path'], $item['image_url'], $item['image_alt']);
            }
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
            ->each(fn ($path) => StoredPublicImage::retire((string) $path));

        $section->items = array_values($items);
    }

    /** @param array<string, mixed> $originalSettings */
    private function syncQuickHelpIcons(HomepageSection $section, Request $request, array $originalSettings): void
    {
        $settings = is_array($section->settings) ? $section->settings : [];
        $cards = is_array($settings['quick_help_cards'] ?? null) ? $settings['quick_help_cards'] : [];
        $previousCards = is_array($originalSettings['quick_help_cards'] ?? null) ? $originalSettings['quick_help_cards'] : [];

        foreach ($cards as $index => &$card) {
            if (! is_array($card)) {
                continue;
            }

            $previous = is_array($previousCards[$index] ?? null) ? $previousCards[$index] : [];
            $previousPath = trim((string) ($previous['icon_path'] ?? '')) ?: null;
            $uploaded = $request->file("settings.quick_help_cards.{$index}.icon_file");

            if ($uploaded) {
                $card['icon_path'] = StoredPublicImage::replace($uploaded, 'homepage/sections/faq/quick-help', $previousPath);
            } elseif ($request->boolean("settings.quick_help_cards.{$index}.remove_icon")) {
                StoredPublicImage::retire($previousPath);
            } elseif ($previousPath !== null) {
                $card['icon_path'] = $previousPath;
            }
            unset($card['remove_icon']);
        }
        unset($card);

        $settings['quick_help_cards'] = $cards;
        $section->settings = $settings;
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
                $imagePath = StoredPublicImage::replace($uploaded, 'homepage/sections/hero/slides', $existingPath);
                $imageUrl = null;
            } elseif ($submittedUrl !== null) {
                if ($submittedUrl !== $existingUrl) {
                    StoredPublicImage::retire($existingPath);
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
            ->each(fn (array $slide) => StoredPublicImage::retire(trim((string) ($slide['image_path'] ?? '')) ?: null));

        $section->hero_slides = array_values($savedSlides);
    }

}
