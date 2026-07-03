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

    public function typeIndex(Request $request, string $type): View
    {
        $selectedType = $this->resolveType($type);

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
            'option' => new JerseyCustomizationOption([
                'type' => $selectedType,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function create(): View
    {
        return view('admin.jersey-customization-options.create', [
            'option' => new JerseyCustomizationOption([
                'type' => JerseyCustomizationType::Color,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => JerseyCustomizationType::options(),
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
            'types' => JerseyCustomizationType::options(),
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

    /** @return array<int, array{number: string, type: JerseyCustomizationType, label: string}> */
    private function typeLinks(JerseyCustomizationType $selectedType): array
    {
        return collect(JerseyCustomizationType::typesForGroup($selectedType->group()))
            ->map(static fn (JerseyCustomizationType $type): array => [
                'number' => $type->menuNumber(),
                'type' => $type,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
