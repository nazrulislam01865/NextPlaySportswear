<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JerseyCustomizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JerseyCustomizationOptionRequest;
use App\Models\JerseyCustomizationOption;
use App\Services\Catalog\JerseyCustomizationOptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JerseyCustomizationOptionController extends Controller
{
    public function __construct(
        private readonly JerseyCustomizationOptionService $optionService
    ) {
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route(
            'admin.jersey-customization-options.type',
            JerseyCustomizationType::Color->value
        );
    }

    public function typeIndex(Request $request, string $type): View|RedirectResponse
    {
        $selectedType = $this->resolveType($type);

        if ($selectedType->isSizeChartType()) {
            return redirect()->route('admin.size-option-groups.index', [
                'customization' => $selectedType->group(),
            ]);
        }

        $query = JerseyCustomizationOption::query()
            ->where('type', $selectedType->value)
            ->with('primaryImage')
            ->withCount('images');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return view('admin.jersey-customization-options.type-index', [
            'options' => $query->ordered()->paginate(20)->withQueryString(),
            'type' => $selectedType,
            'typeLinks' => $this->typeLinks($selectedType),
            'filters' => $request->only(['q']),
            'fabricImportSources' => $this->fabricImportSources($selectedType),
            'option' => new JerseyCustomizationOption([
                'type' => $selectedType,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function importFabrics(Request $request, string $type): RedirectResponse
    {
        $targetType = $this->resolveType($type);

        abort_unless(in_array($targetType, JerseyCustomizationType::fabricTypes(), true), 404);

        $sourceValues = collect(JerseyCustomizationType::fabricTypes())
            ->reject(static fn (JerseyCustomizationType $sourceType): bool => $sourceType === $targetType)
            ->map(static fn (JerseyCustomizationType $sourceType): string => $sourceType->value)
            ->values()
            ->all();

        $validated = $request->validate([
            'source_type' => ['required', Rule::in($sourceValues)],
        ]);

        $sourceType = JerseyCustomizationType::from($validated['source_type']);

        $selection = $request->validate([
            'source_option_ids' => ['required', 'array', 'min:1'],
            'source_option_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('jersey_customization_options', 'id')
                    ->where(static fn ($query) => $query->where('type', $sourceType->value)),
            ],
        ], [
            'source_option_ids.required' => 'Choose at least one fabric option to import.',
            'source_option_ids.min' => 'Choose at least one fabric option to import.',
            'source_option_ids.*.exists' => 'One of the selected fabric options does not belong to the selected source list.',
        ]);

        $selectedOptionIds = collect($selection['source_option_ids'])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $result = $this->optionService->importFromType(
            $sourceType,
            $targetType,
            $request->user('admin')?->getKey(),
            $selectedOptionIds
        );

        $message = $result['imported'].' selected '.$targetType->label().' option'.($result['imported'] === 1 ? '' : 's').' imported from '.$sourceType->label().'.';

        if ($result['skipped'] > 0) {
            $message .= ' '.$result['skipped'].' duplicate '.($result['skipped'] === 1 ? 'option was' : 'options were').' skipped.';
        }

        return redirect()
            ->route('admin.jersey-customization-options.type', $targetType->value)
            ->with('status', $message);
    }

    public function create(): View
    {
        return view('admin.jersey-customization-options.create', [
            'option' => new JerseyCustomizationOption([
                'type' => JerseyCustomizationType::Color,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => JerseyCustomizationType::masterDataOptions(),
        ]);
    }

    public function store(JerseyCustomizationOptionRequest $request): RedirectResponse
    {
        $option = $this->optionService->create($request);

        if ($request->boolean('_return_to_type')) {
            $type = $option->type instanceof JerseyCustomizationType
                ? $option->type->value
                : (string) $option->type;

            return redirect()
                ->route('admin.jersey-customization-options.type', $type)
                ->with('status', $option->name.' created successfully.');
        }

        return redirect()
            ->route('admin.jersey-customization-options.edit', $option)
            ->with('status', 'Customization option created successfully.');
    }

    public function edit(JerseyCustomizationOption $jerseyCustomizationOption): View
    {
        $jerseyCustomizationOption->load('images');

        return view('admin.jersey-customization-options.edit', [
            'option' => $jerseyCustomizationOption,
            'types' => JerseyCustomizationType::masterDataOptions(),
        ]);
    }

    public function update(
        JerseyCustomizationOptionRequest $request,
        JerseyCustomizationOption $jerseyCustomizationOption
    ): RedirectResponse {
        $option = $this->optionService->update($jerseyCustomizationOption, $request);

        if ($request->boolean('_return_to_type')) {
            $type = $option->type instanceof JerseyCustomizationType
                ? $option->type->value
                : (string) $option->type;

            return redirect()
                ->route('admin.jersey-customization-options.type', $type)
                ->with('status', $option->name.' updated successfully.');
        }

        return redirect()
            ->route('admin.jersey-customization-options.edit', $option)
            ->with('status', 'Customization option updated successfully.');
    }

    public function destroy(
        JerseyCustomizationOption $jerseyCustomizationOption
    ): RedirectResponse {
        $type = $jerseyCustomizationOption->type instanceof JerseyCustomizationType
            ? $jerseyCustomizationOption->type->value
            : (string) $jerseyCustomizationOption->type;

        $this->optionService->delete($jerseyCustomizationOption);

        return redirect()
            ->route('admin.jersey-customization-options.type', $type ?: JerseyCustomizationType::Color->value)
            ->with('status', 'Customization option deleted successfully.');
    }

    private function resolveType(string $type): JerseyCustomizationType
    {
        $selectedType = JerseyCustomizationType::tryFrom($type);

        abort_if($selectedType === null, 404);

        return $selectedType;
    }

    /**
     * @return array<int, array{
     *     value: string,
     *     label: string,
     *     group_label: string,
     *     count: int,
     *     available_count: int,
     *     options: array<int, array{id: int, name: string, slug: string, description: ?string, image_url: ?string, exists: bool}>
     * }>
     */
    private function fabricImportSources(JerseyCustomizationType $selectedType): array
    {
        if (! in_array($selectedType, JerseyCustomizationType::fabricTypes(), true)) {
            return [];
        }

        $sourceTypes = collect(JerseyCustomizationType::fabricTypes())
            ->reject(static fn (JerseyCustomizationType $type): bool => $type === $selectedType)
            ->values();

        $sourceTypeValues = $sourceTypes
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $existingTargetSlugs = JerseyCustomizationOption::query()
            ->where('type', $selectedType->value)
            ->pluck('slug')
            ->map(static fn (string $slug): string => strtolower($slug))
            ->all();

        $sourceOptions = JerseyCustomizationOption::query()
            ->whereIn('type', $sourceTypeValues)
            ->with('primaryImage')
            ->ordered()
            ->get()
            ->groupBy(static fn (JerseyCustomizationOption $option): string => $option->type instanceof JerseyCustomizationType
                ? $option->type->value
                : (string) $option->type);

        return $sourceTypes
            ->map(function (JerseyCustomizationType $type) use ($sourceOptions, $existingTargetSlugs): array {
                $options = $sourceOptions->get($type->value, collect())
                    ->map(function (JerseyCustomizationOption $option) use ($existingTargetSlugs): array {
                        $slug = Str::slug($option->slug ?: $option->name);
                        $exists = $slug === '' || in_array(strtolower($slug), $existingTargetSlugs, true);

                        return [
                            'id' => (int) $option->id,
                            'name' => $option->name,
                            'slug' => $slug,
                            'description' => $option->description,
                            'image_url' => $option->primaryImage?->publicUrl(),
                            'exists' => $exists,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'group_label' => $type->groupLabel(),
                    'count' => count($options),
                    'available_count' => count(array_filter($options, static fn (array $option): bool => ! $option['exists'])),
                    'options' => $options,
                ];
            })
            ->all();
    }

    /** @return array<int, array{number: string, type: JerseyCustomizationType, label: string}> */
    private function typeLinks(JerseyCustomizationType $selectedType): array
    {
        return collect(JerseyCustomizationType::menuTypesForGroup($selectedType->group()))
            ->map(static fn (JerseyCustomizationType $type): array => [
                'number' => $type->menuNumber(),
                'type' => $type,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
