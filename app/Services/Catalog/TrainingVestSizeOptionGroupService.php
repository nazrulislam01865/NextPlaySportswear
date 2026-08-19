<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\TrainingVestSizeOptionGroupRequest;
use App\Models\TrainingVestSizeOptionGroup;
use App\Services\Security\SafeHtmlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainingVestSizeOptionGroupService
{
    public function __construct(
        private readonly SafeHtmlService $safeHtml,
        private readonly TrainingVestSharedCustomizationSyncService $sharedSync,
    ) {
    }

    public function create(TrainingVestSizeOptionGroupRequest $request): TrainingVestSizeOptionGroup
    {
        return DB::transaction(function () use ($request): TrainingVestSizeOptionGroup {
            $group = TrainingVestSizeOptionGroup::query()->create($this->payload($request));
            $this->syncSizes($group, $request->validated('sizes', []));
            $this->syncChartImage($group, $request);
            $group = $group->refresh()->load('sizes');
            $this->sharedSync->syncSizeGroup($group);

            return $group;
        });
    }

    public function update(TrainingVestSizeOptionGroup $group, TrainingVestSizeOptionGroupRequest $request): TrainingVestSizeOptionGroup
    {
        return DB::transaction(function () use ($group, $request): TrainingVestSizeOptionGroup {
            $group->update($this->payload($request, $group));
            $this->syncSizes($group, $request->validated('sizes', []));
            $this->syncChartImage($group, $request);
            $group = $group->refresh()->load('sizes');
            $this->sharedSync->syncSizeGroup($group);

            return $group;
        });
    }

    public function delete(TrainingVestSizeOptionGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $this->sharedSync->deleteSizeGroupMirrors($group);
            $this->deleteStoredImage($group->chart_image_path);
            $group->delete();
        });
    }

    /** @return array<string, mixed> */
    private function payload(TrainingVestSizeOptionGroupRequest $request, ?TrainingVestSizeOptionGroup $group = null): array
    {
        $validated = $request->validated();
        $adminId = $request->user('admin')?->getKey();
        $hasImageInput = $request->file('chart_image') !== null || filled($validated['chart_image_url'] ?? null);
        $chartHtml = $hasImageInput ? null : $this->safeHtml->sanitize($validated['chart_html'] ?? null);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'audience' => $validated['audience'],
            'description_html' => $this->safeHtml->sanitize($validated['description_html'] ?? null),
            'chart_html' => $chartHtml,
            'chart_title' => null,
            'chart_note' => null,
            'chart_columns' => [],
            'chart_rows' => [],
            'chart_image_url' => $validated['chart_image_url'] ?? null,
            'is_active' => true,
            'sort_order' => $group?->sort_order ?? 0,
            'created_by' => $group?->created_by ?? $adminId,
            'updated_by' => $adminId,
        ];
    }

    /** @param array<int, array<string, mixed>> $sizes */
    private function syncSizes(TrainingVestSizeOptionGroup $group, array $sizes): void
    {
        $group->sizes()->delete();

        foreach ($sizes as $index => $size) {
            $group->sizes()->create([
                'label' => $size['label'],
                'code' => $size['code'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncChartImage(TrainingVestSizeOptionGroup $group, TrainingVestSizeOptionGroupRequest $request): void
    {
        $path = $group->chart_image_path;
        $url = $request->validated('chart_image_url');
        $hasFormattedChart = filled(strip_tags((string) $request->validated('chart_html')));

        if ($request->boolean('clear_chart_image') || $hasFormattedChart) {
            $this->deleteStoredImage($path);
            $path = null;
            $url = null;
        }

        if (! $hasFormattedChart && ($uploaded = $request->file('chart_image'))) {
            $this->deleteStoredImage($path);
            $path = $uploaded->store("catalog/training-vest-size-options/{$group->id}", 'public');
            $url = null;
        } elseif (! $hasFormattedChart && filled($url)) {
            $this->deleteStoredImage($path);
            $path = null;
        }

        $group->forceFill(['chart_image_path' => $path, 'chart_image_url' => $url])->save();
    }

    private function deleteStoredImage(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
