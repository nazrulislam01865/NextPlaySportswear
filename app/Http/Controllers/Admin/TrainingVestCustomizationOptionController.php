<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SizeAudience;
use App\Enums\TrainingVestCustomizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrainingVestCustomizationOptionRequest;
use App\Http\Requests\Admin\TrainingVestSizeOptionGroupRequest;
use App\Models\TrainingVestCustomizationOption;
use App\Models\TrainingVestSizeOptionGroup;
use App\Services\Catalog\TrainingVestCustomizationOptionService;
use App\Services\Catalog\TrainingVestSizeOptionGroupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingVestCustomizationOptionController extends Controller
{
    public function __construct(
        private readonly TrainingVestCustomizationOptionService $optionService,
        private readonly TrainingVestSizeOptionGroupService $sizeGroupService,
    ) {
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route(
            'admin.training-vest-customization-options.type',
            TrainingVestCustomizationType::Color->value
        );
    }

    public function typeIndex(Request $request, string $type): View
    {
        $selectedType = $this->resolveType($type);

        if ($selectedType === TrainingVestCustomizationType::Size) {
            return $this->sizeIndex($request, $selectedType);
        }

        $query = TrainingVestCustomizationOption::query()
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

        return view('admin.training-vest-customization-options.type-index', [
            'options' => $query->ordered()->paginate(20)->withQueryString(),
            'type' => $selectedType,
            'typeLinks' => $this->typeLinks(),
            'filters' => $request->only(['q']),
            'option' => new TrainingVestCustomizationOption([
                'type' => $selectedType,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function create(Request $request): View
    {
        $selectedType = TrainingVestCustomizationType::tryFrom((string) $request->query('type'))
            ?? TrainingVestCustomizationType::Color;

        if ($selectedType === TrainingVestCustomizationType::Size) {
            return $this->createSizeGroup($request);
        }

        return view('admin.training-vest-customization-options.create', [
            'option' => new TrainingVestCustomizationOption([
                'type' => $selectedType,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => TrainingVestCustomizationType::masterDataOptions(),
        ]);
    }

    public function store(TrainingVestCustomizationOptionRequest $request): RedirectResponse
    {
        $option = $this->optionService->create($request);

        if ($request->boolean('_return_to_type')) {
            $type = $option->type instanceof TrainingVestCustomizationType
                ? $option->type->value
                : (string) $option->type;

            return redirect()
                ->route('admin.training-vest-customization-options.type', $type)
                ->with('status', $option->name.' created successfully.');
        }

        return redirect()
            ->route('admin.training-vest-customization-options.edit', $option)
            ->with('status', 'Training vest customization option created successfully.');
    }

    public function edit(TrainingVestCustomizationOption $trainingVestCustomizationOption): View
    {
        $trainingVestCustomizationOption->load('images');

        return view('admin.training-vest-customization-options.edit', [
            'option' => $trainingVestCustomizationOption,
            'types' => TrainingVestCustomizationType::masterDataOptions(),
        ]);
    }

    public function update(
        TrainingVestCustomizationOptionRequest $request,
        TrainingVestCustomizationOption $trainingVestCustomizationOption
    ): RedirectResponse {
        $option = $this->optionService->update($trainingVestCustomizationOption, $request);

        if ($request->boolean('_return_to_type')) {
            $type = $option->type instanceof TrainingVestCustomizationType
                ? $option->type->value
                : (string) $option->type;

            return redirect()
                ->route('admin.training-vest-customization-options.type', $type)
                ->with('status', $option->name.' updated successfully.');
        }

        return redirect()
            ->route('admin.training-vest-customization-options.edit', $option)
            ->with('status', 'Training vest customization option updated successfully.');
    }

    public function destroy(
        TrainingVestCustomizationOption $trainingVestCustomizationOption
    ): RedirectResponse {
        $type = $trainingVestCustomizationOption->type instanceof TrainingVestCustomizationType
            ? $trainingVestCustomizationOption->type->value
            : (string) $trainingVestCustomizationOption->type;

        $this->optionService->delete($trainingVestCustomizationOption);

        return redirect()
            ->route('admin.training-vest-customization-options.type', $type ?: TrainingVestCustomizationType::Color->value)
            ->with('status', 'Training vest customization option deleted successfully.');
    }

    public function createSizeGroup(Request $request): View
    {
        return view('admin.training-vest-customization-options.size-create', [
            'group' => new TrainingVestSizeOptionGroup([
                'audience' => SizeAudience::Unisex,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'audiences' => SizeAudience::options(),
            'type' => TrainingVestCustomizationType::Size,
            'typeLinks' => $this->typeLinks(),
        ]);
    }

    public function storeSizeGroup(TrainingVestSizeOptionGroupRequest $request): RedirectResponse
    {
        $group = $this->sizeGroupService->create($request);

        return redirect()
            ->route('admin.training-vest-size-option-groups.edit', $group)
            ->with('status', 'Training vest size group created successfully.');
    }

    public function editSizeGroup(TrainingVestSizeOptionGroup $trainingVestSizeOptionGroup): View
    {
        $trainingVestSizeOptionGroup->load('sizes');

        return view('admin.training-vest-customization-options.size-edit', [
            'group' => $trainingVestSizeOptionGroup,
            'audiences' => SizeAudience::options(),
            'type' => TrainingVestCustomizationType::Size,
            'typeLinks' => $this->typeLinks(),
        ]);
    }

    public function updateSizeGroup(
        TrainingVestSizeOptionGroupRequest $request,
        TrainingVestSizeOptionGroup $trainingVestSizeOptionGroup
    ): RedirectResponse {
        $group = $this->sizeGroupService->update($trainingVestSizeOptionGroup, $request);

        return redirect()
            ->route('admin.training-vest-size-option-groups.edit', $group)
            ->with('status', 'Training vest size group updated successfully.');
    }

    public function destroySizeGroup(TrainingVestSizeOptionGroup $trainingVestSizeOptionGroup): RedirectResponse
    {
        $this->sizeGroupService->delete($trainingVestSizeOptionGroup);

        return redirect()
            ->route('admin.training-vest-customization-options.type', TrainingVestCustomizationType::Size->value)
            ->with('status', 'Training vest size group deleted successfully.');
    }

    private function sizeIndex(Request $request, TrainingVestCustomizationType $selectedType): View
    {
        $query = TrainingVestSizeOptionGroup::query()
            ->with('sizes')
            ->withCount('sizes');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if ($request->filled('audience')) {
            $query->where('audience', $request->query('audience'));
        }

        return view('admin.training-vest-customization-options.size-index', [
            'groups' => $query->ordered()->paginate(30)->withQueryString(),
            'audiences' => SizeAudience::options(),
            'filters' => $request->only(['q', 'audience']),
            'type' => $selectedType,
            'typeLinks' => $this->typeLinks(),
        ]);
    }

    private function resolveType(string $type): TrainingVestCustomizationType
    {
        $selectedType = TrainingVestCustomizationType::tryFrom($type);

        abort_if($selectedType === null, 404);

        return $selectedType;
    }

    /** @return array<int, array{number: string, type: TrainingVestCustomizationType, label: string}> */
    private function typeLinks(): array
    {
        return collect(TrainingVestCustomizationType::menuTypes())
            ->map(static fn (TrainingVestCustomizationType $type): array => [
                'number' => $type->menuNumber(),
                'type' => $type,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
