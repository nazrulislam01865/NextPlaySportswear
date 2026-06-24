<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\JerseyCustomizationOptionRequest;
use App\Models\JerseyCustomizationOption;
use App\Models\JerseyCustomizationOptionImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JerseyCustomizationOptionService
{
    public function create(JerseyCustomizationOptionRequest $request): JerseyCustomizationOption
    {
        return DB::transaction(function () use ($request): JerseyCustomizationOption {
            $option = JerseyCustomizationOption::query()->create($this->optionPayload($request));
            $this->syncImages($option, $request);

            return $option->load('images');
        });
    }

    public function update(
        JerseyCustomizationOption $option,
        JerseyCustomizationOptionRequest $request
    ): JerseyCustomizationOption {
        return DB::transaction(function () use ($option, $request): JerseyCustomizationOption {
            $option->update($this->optionPayload($request, $option));
            $this->syncImages($option, $request);

            return $option->refresh()->load('images');
        });
    }

    public function delete(JerseyCustomizationOption $option): void
    {
        DB::transaction(function () use ($option): void {
            $option->load('images');

            foreach ($option->images as $image) {
                $this->deleteStoredImage($image->image_path);
            }

            $option->delete();
        });
    }

    /** @return array<string, mixed> */
    private function optionPayload(
        JerseyCustomizationOptionRequest $request,
        ?JerseyCustomizationOption $option = null
    ): array {
        $adminId = $request->user('admin')?->getKey();

        return array_merge(
            Arr::only($request->validated(), [
                'type',
                'name',
                'slug',
                'color_hex',
                'description',
                'is_active',
                'sort_order',
            ]),
            [
                'created_by' => $option?->created_by ?? $adminId,
                'updated_by' => $adminId,
            ]
        );
    }

    private function syncImages(
        JerseyCustomizationOption $option,
        JerseyCustomizationOptionRequest $request
    ): void {
        $existing = $option->images()->get()->keyBy('id');
        $keptIds = [];
        $primaryId = null;

        foreach ($request->validated('images', []) as $index => $input) {
            $uploaded = $request->file("images.{$index}.image_file");
            $existingId = (int) ($input['existing_id'] ?? 0);
            $imageUrl = trim((string) ($input['image_url'] ?? ''));
            $name = trim((string) ($input['name'] ?? ''));

            if ($existingId === 0 && ! $uploaded && $imageUrl === '' && $name === '') {
                continue;
            }

            $image = $existingId > 0
                ? $existing->get($existingId)
                : new JerseyCustomizationOptionImage([
                    'jersey_customization_option_id' => $option->id,
                ]);

            if ($existingId > 0 && ! $image) {
                throw ValidationException::withMessages([
                    "images.{$index}.existing_id" => 'The selected image does not belong to this option.',
                ]);
            }

            $imagePath = $image->image_path;
            $storedUrl = $image->image_url;

            if ($uploaded) {
                $this->deleteStoredImage($imagePath);
                $imagePath = $uploaded->store(
                    "catalog/jersey-customization-options/{$option->id}",
                    'public'
                );
                $storedUrl = null;
            } elseif ($imageUrl !== '') {
                $this->deleteStoredImage($imagePath);
                $imagePath = null;
                $storedUrl = $imageUrl;
            }

            $image->fill([
                'jersey_customization_option_id' => $option->id,
                'name' => $name,
                'image_path' => $imagePath,
                'image_url' => $storedUrl,
                'is_primary' => false,
                'sort_order' => (int) ($input['sort_order'] ?? $index),
            ])->save();

            $keptIds[] = $image->id;

            if ($primaryId === null && (bool) ($input['is_primary'] ?? false)) {
                $primaryId = $image->id;
            }
        }

        $option->images()
            ->whereNotIn('id', $keptIds ?: [0])
            ->get()
            ->each(function (JerseyCustomizationOptionImage $image): void {
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
