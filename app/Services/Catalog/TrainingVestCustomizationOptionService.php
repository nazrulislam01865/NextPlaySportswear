<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\TrainingVestCustomizationOptionRequest;
use App\Models\TrainingVestCustomizationOption;
use App\Models\TrainingVestCustomizationOptionImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TrainingVestCustomizationOptionService
{
    public function __construct(private readonly TrainingVestSharedCustomizationSyncService $sharedSync)
    {
    }
    public function create(TrainingVestCustomizationOptionRequest $request): TrainingVestCustomizationOption
    {
        return DB::transaction(function () use ($request): TrainingVestCustomizationOption {
            $option = TrainingVestCustomizationOption::query()->create($this->optionPayload($request));
            $this->syncImages($option, $request);
            $option->load('images');
            $this->sharedSync->syncLegacyOption($option);

            return $option;
        });
    }

    public function update(
        TrainingVestCustomizationOption $option,
        TrainingVestCustomizationOptionRequest $request
    ): TrainingVestCustomizationOption {
        return DB::transaction(function () use ($option, $request): TrainingVestCustomizationOption {
            $previousType = $option->type;
            $previousSlug = $option->slug;
            $option->update($this->optionPayload($request, $option));
            $this->syncImages($option, $request);
            $option = $option->refresh()->load('images');
            $this->sharedSync->syncLegacyOption($option, $previousType, $previousSlug);

            return $option;
        });
    }

    public function delete(TrainingVestCustomizationOption $option): void
    {
        DB::transaction(function () use ($option): void {
            $option->load('images');

            $this->sharedSync->deleteLegacyOptionMirror($option);

            foreach ($option->images as $image) {
                $this->deleteStoredImage($image->image_path);
            }

            $option->delete();
        });
    }

    /** @return array<string, mixed> */
    private function optionPayload(
        TrainingVestCustomizationOptionRequest $request,
        ?TrainingVestCustomizationOption $option = null
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
        TrainingVestCustomizationOption $option,
        TrainingVestCustomizationOptionRequest $request
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
                : new TrainingVestCustomizationOptionImage([
                    'training_vest_customization_option_id' => $option->id,
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
                    "catalog/training-vest-customization-options/{$option->id}",
                    'public'
                );
                $storedUrl = null;
            } elseif ($imageUrl !== '') {
                $this->deleteStoredImage($imagePath);
                $imagePath = null;
                $storedUrl = $imageUrl;
            }

            $image->fill([
                'training_vest_customization_option_id' => $option->id,
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
            ->each(function (TrainingVestCustomizationOptionImage $image): void {
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
