<?php

namespace App\Services\Catalog;

use App\Enums\JerseyCustomizationType;
use App\Http\Requests\Admin\JerseyCustomizationOptionRequest;
use App\Models\JerseyCustomizationOption;
use App\Models\JerseyCustomizationOptionImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    /**
     * Copy reusable fabric options from one fabric list into another fabric list.
     *
     * @return array{total:int,imported:int,skipped:int}
     */
    public function importFromType(
        JerseyCustomizationType $sourceType,
        JerseyCustomizationType $targetType,
        ?int $adminId = null,
        ?array $sourceOptionIds = null
    ): array {
        if (! in_array($sourceType, JerseyCustomizationType::fabricTypes(), true)) {
            throw ValidationException::withMessages([
                'source_type' => 'The selected source must be a fabric-based customization type.',
            ]);
        }

        if (! in_array($targetType, JerseyCustomizationType::fabricTypes(), true)) {
            throw ValidationException::withMessages([
                'type' => 'Fabric options can only be imported into another fabric-based customization type.',
            ]);
        }

        if ($sourceType === $targetType) {
            throw ValidationException::withMessages([
                'source_type' => 'Choose a different fabric list to import from.',
            ]);
        }

        $sourceOptionIds = $sourceOptionIds === null
            ? null
            : collect($sourceOptionIds)
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

        return DB::transaction(function () use ($sourceType, $targetType, $adminId, $sourceOptionIds): array {
            $sourceOptions = JerseyCustomizationOption::query()
                ->where('type', $sourceType->value)
                ->when($sourceOptionIds !== null, static function ($query) use ($sourceOptionIds): void {
                    $query->whereKey($sourceOptionIds);
                })
                ->with('images')
                ->ordered()
                ->get();

            $existingSlugs = JerseyCustomizationOption::query()
                ->where('type', $targetType->value)
                ->pluck('slug')
                ->map(static fn (string $slug): string => strtolower($slug))
                ->all();

            $nextSortOrder = ((int) JerseyCustomizationOption::query()
                ->where('type', $targetType->value)
                ->max('sort_order')) + 1;

            $result = [
                'total' => $sourceOptions->count(),
                'imported' => 0,
                'skipped' => 0,
            ];

            foreach ($sourceOptions as $sourceOption) {
                $slug = Str::slug($sourceOption->slug ?: $sourceOption->name);

                if ($slug === '' || in_array(strtolower($slug), $existingSlugs, true)) {
                    $result['skipped']++;
                    continue;
                }

                $targetOption = JerseyCustomizationOption::query()->create([
                    'type' => $targetType->value,
                    'name' => $sourceOption->name,
                    'slug' => $slug,
                    'color_hex' => null,
                    'description' => $sourceOption->description,
                    'is_active' => (bool) $sourceOption->is_active,
                    'sort_order' => $nextSortOrder++,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);

                $this->cloneImagesForImportedOption($sourceOption, $targetOption);

                $existingSlugs[] = strtolower($slug);
                $result['imported']++;
            }

            return $result;
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
                $group = $option->type?->group() ?? 'product';
                $imagePath = $uploaded->store(
                    "catalog/customization-options/{$group}/{$option->id}",
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

    private function cloneImagesForImportedOption(
        JerseyCustomizationOption $sourceOption,
        JerseyCustomizationOption $targetOption
    ): void {
        $sourceOption->loadMissing('images');

        foreach ($sourceOption->images as $image) {
            $imagePath = null;
            $imageUrl = $image->image_url;

            if (filled($image->image_path) && Storage::disk('public')->exists($image->image_path)) {
                $extension = pathinfo($image->image_path, PATHINFO_EXTENSION) ?: 'jpg';
                $filename = Str::slug(pathinfo($image->image_path, PATHINFO_FILENAME)) ?: 'fabric';
                $group = $targetOption->type instanceof JerseyCustomizationType
                    ? $targetOption->type->group()
                    : $targetOption->type;
                $targetDirectory = 'catalog/customization-options/'.($group ?: 'product').'/'.$targetOption->id;
                $imagePath = $targetDirectory.'/'.$filename.'-'.Str::lower(Str::random(8)).'.'.$extension;

                Storage::disk('public')->makeDirectory($targetDirectory);
                Storage::disk('public')->copy($image->image_path, $imagePath);
                $imageUrl = null;
            }

            if (! filled($imagePath) && ! filled($imageUrl)) {
                continue;
            }

            $targetOption->images()->create([
                'name' => $image->name,
                'image_path' => $imagePath,
                'image_url' => $imageUrl,
                'is_primary' => (bool) $image->is_primary,
                'sort_order' => (int) $image->sort_order,
            ]);
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
