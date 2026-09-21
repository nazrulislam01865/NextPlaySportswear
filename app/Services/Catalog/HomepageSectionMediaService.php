<?php

namespace App\Services\Catalog;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageSectionMediaService
{
    public function __construct(private readonly HomepageStagedUploadService $stagedUploads)
    {
    }

    public function sync(HomepageSection $section, Request $request): void
    {
        $this->syncSectionMedia($section, $request);
        $this->syncItemMedia($section, $request);
        $section->save();
    }

    private function syncSectionMedia(HomepageSection $section, Request $request): void
    {
        $uploaded = $request->file('image_file');
        $uploadToken = trim((string) $request->input('image_upload_token', ''));
        $imageUrl = trim((string) $request->input('image_url', ''));

        if ($request->boolean('remove_image')) {
            $this->deleteSectionPath($section, $section->image_path);
            $section->image_path = null;
            $section->image_url = null;
        }

        if ($uploadToken !== '') {
            $newPath = $this->stagedUploads->consumeToPublic(
                (int) $request->user()->id,
                $uploadToken,
                "homepage/sections/{$section->key}",
            );
            $this->deleteSectionPath($section, $section->image_path);
            $section->image_path = $newPath;
            $section->image_url = null;
        } elseif ($uploaded) {
            $this->deleteSectionPath($section, $section->image_path);
            $section->image_path = $uploaded->store("homepage/sections/{$section->key}", 'public');
            $section->image_url = null;
        } elseif ($imageUrl !== '') {
            $this->deleteSectionPath($section, $section->image_path);
            $section->image_path = null;
            $section->image_url = $imageUrl;
        }

    }

    private function syncItemMedia(HomepageSection $section, Request $request): void
    {
        $existing = collect(is_array($section->items) ? $section->items : [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['id'] ?? null))
            ->keyBy(fn (array $item): string => (string) $item['id']);

        $submitted = collect((array) $request->input('items', []))
            ->filter(fn ($item): bool => is_array($item) && filled($item['id'] ?? null));

        $saved = [];
        $seen = [];

        foreach ($submitted as $index => $row) {
            $id = trim((string) $row['id']);
            $seen[$id] = true;
            $old = (array) ($existing->get($id) ?? []);
            $path = trim((string) ($old['image_path'] ?? '')) ?: null;
            $oldUrl = trim((string) ($old['image_url'] ?? '')) ?: null;
            $submittedUrl = trim((string) ($row['image_url'] ?? '')) ?: null;
            $url = $submittedUrl ?? $oldUrl;

            if ($request->boolean("items.$index.remove_image")) {
                $this->deleteOwnedItemPath($section, $id, $path);
                $path = null;
                $url = null;
            }

            $uploadToken = trim((string) ($row['image_upload_token'] ?? ''));
            if ($uploadToken !== '') {
                $newPath = $this->stagedUploads->consumeToPublic(
                    (int) $request->user()->id,
                    $uploadToken,
                    "homepage/sections/{$section->key}/items/{$id}",
                );
                $this->deleteOwnedItemPath($section, $id, $path);
                $path = $newPath;
                $url = null;
            } elseif ($upload = $request->file("items.$index.image_file")) {
                $this->deleteOwnedItemPath($section, $id, $path);
                $path = $upload->store("homepage/sections/{$section->key}/items/{$id}", 'public');
                $url = null;
            } elseif ($submittedUrl !== null && $submittedUrl !== $oldUrl) {
                $this->deleteOwnedItemPath($section, $id, $path);
                $path = null;
                $url = $submittedUrl;
            }

            $cleanRow = $this->cleanSubmittedItem($row);
            $saved[] = array_filter(array_merge($old, $cleanRow, [
                'id' => $id,
                'image_path' => $path,
                'image_url' => $url,
            ]), fn ($value): bool => $value !== null && $value !== '');
        }

        $existing->reject(fn (array $item, string $id): bool => isset($seen[$id]))
            ->each(fn (array $item, string $id) => $this->deleteOwnedItemPath($section, $id, $item['image_path'] ?? null));

        $section->items = array_values($saved);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function cleanSubmittedItem(array $row): array
    {
        $clean = [];
        foreach (['id', 'title', 'subtitle', 'description', 'url', 'label', 'image_alt', 'image_url'] as $field) {
            $value = trim(strip_tags((string) ($row[$field] ?? '')));
            if ($value !== '') {
                $clean[$field] = $value;
            }
        }
        $categoryId = (int) ($row['category_id'] ?? 0);
        if ($categoryId > 0) {
            $clean['category_id'] = $categoryId;
        }
        return $clean;
    }

    private function deleteOwnedItemPath(HomepageSection $section, string $itemId, mixed $path): void
    {
        $path = trim((string) $path);
        $prefix = "homepage/sections/{$section->key}/items/{$itemId}/";
        if ($path !== '' && str_starts_with($path, $prefix) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteSectionPath(HomepageSection $section, mixed $path): void
    {
        $path = trim((string) $path);
        $prefix = "homepage/sections/{$section->key}/";
        if ($path !== '' && str_starts_with($path, $prefix) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
