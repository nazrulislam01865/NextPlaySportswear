<?php

namespace App\Services\Catalog;

use App\Enums\JerseyCustomizationType;
use App\Enums\TrainingVestCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\TrainingVestCustomizationOption;
use App\Models\TrainingVestSizeOptionGroup;
use App\Support\TrainingVestCustomizationBridge;

final class TrainingVestSharedCustomizationSyncService
{
    public function syncLegacyOption(
        TrainingVestCustomizationOption $option,
        TrainingVestCustomizationType|string|null $previousType = null,
        ?string $previousSlug = null,
    ): JerseyCustomizationOption {
        $option->loadMissing('images');
        $sharedType = TrainingVestCustomizationBridge::sharedType($option->type);

        $mirror = null;
        if ($previousType !== null && filled($previousSlug)) {
            $previousSharedType = TrainingVestCustomizationBridge::sharedType($previousType);
            $mirror = JerseyCustomizationOption::query()
                ->where('type', $previousSharedType->value)
                ->where('slug', $previousSlug)
                ->first();
        }

        $mirror ??= JerseyCustomizationOption::query()->firstOrNew([
            'type' => $sharedType->value,
            'slug' => $option->slug,
        ]);

        $mirror->fill([
            'type' => $sharedType->value,
            'name' => $option->name,
            'slug' => $option->slug,
            'color_hex' => $option->color_hex,
            'description' => $option->description,
            'is_active' => (bool) $option->is_active,
            'sort_order' => (int) $option->sort_order,
            'created_by' => $mirror->created_by ?? $option->created_by,
            'updated_by' => $option->updated_by,
        ])->save();

        $mirror->images()->delete();
        foreach ($option->images as $image) {
            $mirror->images()->create([
                'name' => $image->name,
                'image_path' => $image->image_path,
                'image_url' => $image->image_url,
                'is_primary' => (bool) $image->is_primary,
                'sort_order' => (int) $image->sort_order,
            ]);
        }

        return $mirror->refresh()->load('images');
    }

    public function deleteLegacyOptionMirror(TrainingVestCustomizationOption $option): void
    {
        $sharedType = TrainingVestCustomizationBridge::sharedType($option->type);

        JerseyCustomizationOption::query()
            ->where('type', $sharedType->value)
            ->where('slug', $option->slug)
            ->delete();
    }

    public function syncSizeGroup(TrainingVestSizeOptionGroup $group): void
    {
        $group->loadMissing('sizes');
        $sharedType = JerseyCustomizationType::TrainingVestSizeOption;
        $activeSlugs = [];

        foreach ($group->sizes as $size) {
            $slug = TrainingVestCustomizationBridge::sizeMirrorSlug((int) $group->id, (string) $size->code);
            $activeSlugs[] = $slug;

            JerseyCustomizationOption::query()->updateOrCreate(
                ['type' => $sharedType->value, 'slug' => $slug],
                [
                    'name' => $group->name.' — '.$size->label,
                    'color_hex' => null,
                    'description' => filled($group->description_html) ? trim(strip_tags((string) $group->description_html)) : null,
                    'is_active' => (bool) $group->is_active && (bool) $size->is_active,
                    'sort_order' => ((int) $group->sort_order * 1000) + (int) $size->sort_order,
                    'created_by' => $group->created_by,
                    'updated_by' => $group->updated_by,
                ]
            );
        }

        $stale = JerseyCustomizationOption::query()
            ->where('type', $sharedType->value)
            ->where('slug', 'like', TrainingVestCustomizationBridge::sizeMirrorPrefix((int) $group->id).'%');

        if ($activeSlugs !== []) {
            $stale->whereNotIn('slug', $activeSlugs);
        }

        $stale->delete();
    }

    public function deleteSizeGroupMirrors(TrainingVestSizeOptionGroup $group): void
    {
        JerseyCustomizationOption::query()
            ->where('type', JerseyCustomizationType::TrainingVestSizeOption->value)
            ->where('slug', 'like', TrainingVestCustomizationBridge::sizeMirrorPrefix((int) $group->id).'%')
            ->delete();
    }
}
