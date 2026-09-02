<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\WorldCupCustomizationOptionRequest;
use App\Models\WorldCupCustomizationOption;
use App\Models\WorldCupCustomizationOptionImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorldCupCustomizationOptionService
{
    public function create(WorldCupCustomizationOptionRequest $request): WorldCupCustomizationOption
    {
        return DB::transaction(function () use ($request): WorldCupCustomizationOption {
            $option = WorldCupCustomizationOption::query()->create($this->payload($request));
            $this->syncImages($option, $request);
            return $option->load('images');
        });
    }

    public function update(WorldCupCustomizationOption $option, WorldCupCustomizationOptionRequest $request): WorldCupCustomizationOption
    {
        return DB::transaction(function () use ($option, $request): WorldCupCustomizationOption {
            $option->update($this->payload($request, $option));
            $this->syncImages($option, $request);
            return $option->refresh()->load('images');
        });
    }

    public function delete(WorldCupCustomizationOption $option): void
    {
        DB::transaction(function () use ($option): void {
            $option->load('images');
            foreach ($option->images as $image) {
                $this->deleteStoredImage($image->image_path);
            }
            $option->delete();
        });
    }

    /** @return array<string,mixed> */
    private function payload(WorldCupCustomizationOptionRequest $request, ?WorldCupCustomizationOption $option = null): array
    {
        $adminId = $request->user('admin')?->getKey();
        return array_merge(
            Arr::only($request->validated(), ['category_key', 'type', 'name', 'slug', 'description', 'is_active', 'sort_order']),
            ['created_by' => $option?->created_by ?? $adminId, 'updated_by' => $adminId]
        );
    }

    private function syncImages(WorldCupCustomizationOption $option, WorldCupCustomizationOptionRequest $request): void
    {
        $existing = $option->images()->get()->keyBy('id');
        $keptIds = [];
        $primaryId = null;

        foreach ($request->validated('images', []) as $index => $input) {
            $uploaded = $request->file("images.{$index}.image_file");
            $existingId = (int) ($input['existing_id'] ?? 0);
            $url = trim((string) ($input['image_url'] ?? ''));
            $name = trim((string) ($input['name'] ?? ''));
            if ($existingId === 0 && ! $uploaded && $url === '' && $name === '') {
                continue;
            }

            $image = $existingId > 0
                ? $existing->get($existingId)
                : new WorldCupCustomizationOptionImage(['world_cup_customization_option_id' => $option->id]);
            if ($existingId > 0 && ! $image) {
                throw ValidationException::withMessages([
                    "images.{$index}.existing_id" => 'The selected image does not belong to this option.',
                ]);
            }

            $path = $image->image_path;
            $storedUrl = $image->image_url;
            if ($uploaded) {
                $this->deleteStoredImage($path);
                $path = $uploaded->store(
                    "catalog/world-cup-customization/{$option->category_key}/{$option->id}",
                    'public'
                );
                $storedUrl = null;
            } elseif ($url !== '') {
                $this->deleteStoredImage($path);
                $path = null;
                $storedUrl = $url;
            }

            $image->fill([
                'world_cup_customization_option_id' => $option->id,
                'name' => $name,
                'image_path' => $path,
                'image_url' => $storedUrl,
                'is_primary' => false,
                'sort_order' => (int) ($input['sort_order'] ?? $index),
            ])->save();
            $keptIds[] = $image->id;
            if ($primaryId === null && (bool) ($input['is_primary'] ?? false)) {
                $primaryId = $image->id;
            }
        }

        $option->images()->whereNotIn('id', $keptIds ?: [0])->get()->each(function (WorldCupCustomizationOptionImage $image): void {
            $this->deleteStoredImage($image->image_path);
            $image->delete();
        });
        if ($primaryId === null) {
            $primaryId = $option->images()->orderBy('sort_order')->orderBy('id')->value('id');
        }
        $option->images()->update(['is_primary' => false]);
        if ($primaryId !== null) {
            $option->images()->whereKey($primaryId)->update(['is_primary' => true]);
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
