<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\SizeOptionGroupRequest;
use App\Models\SizeOptionGroup;
use App\Services\Security\SafeHtmlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SizeOptionGroupService
{
    public function __construct(private readonly SafeHtmlService $safeHtml)
    {
    }

    public function create(SizeOptionGroupRequest $request): SizeOptionGroup
    {
        return DB::transaction(function () use ($request): SizeOptionGroup {
            $group = SizeOptionGroup::query()->create($this->payload($request));
            $this->syncSizes($group, $request->validated('sizes', []));
            $this->syncChartImage($group, $request);

            return $group->refresh()->load('sizes');
        });
    }

    public function update(SizeOptionGroup $group, SizeOptionGroupRequest $request): SizeOptionGroup
    {
        return DB::transaction(function () use ($group, $request): SizeOptionGroup {
            $group->update($this->payload($request, $group));
            $this->syncSizes($group, $request->validated('sizes', []));
            $this->syncChartImage($group, $request);

            return $group->refresh()->load('sizes');
        });
    }

    public function delete(SizeOptionGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $this->deleteStoredImage($group->chart_image_path);
            $group->delete();
        });
    }

    /** @return array<string, mixed> */
    private function payload(SizeOptionGroupRequest $request, ?SizeOptionGroup $group = null): array
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
            // Legacy structured-chart fields are cleared because the master now uses
            // one formatted chart field or one image, never both.
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
    private function syncSizes(SizeOptionGroup $group, array $sizes): void
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

    private function syncChartImage(SizeOptionGroup $group, SizeOptionGroupRequest $request): void
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
            $path = $uploaded->store("catalog/size-options/{$group->id}", 'public');
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
