<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorldCupCustomizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorldCupCustomizationOptionRequest;
use App\Models\WorldCupCustomizationOption;
use App\Services\Catalog\WorldCupCustomizationOptionService;
use App\Support\WorldCupCustomizationRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldCupCustomizationOptionController extends Controller
{
    public function __construct(private readonly WorldCupCustomizationOptionService $optionService)
    {
    }

    public function index(): RedirectResponse
    {
        return redirect()->route(
            'admin.world-cup-customization-options.type',
            WorldCupCustomizationType::DrawstringMaterialsOption->value
        );
    }

    public function typeIndex(Request $request, string $type): View
    {
        $selectedType = $this->resolveType($type);
        $query = WorldCupCustomizationOption::query()
            ->where('category_key', $selectedType->categoryKey())
            ->where('type', $selectedType->value)
            ->with('primaryImage')
            ->withCount('images');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return view('admin.world-cup-customization-options.type-index', [
            'options' => $query->ordered()->paginate($this->adminPerPage(20))->withQueryString(),
            'type' => $selectedType,
            'typeLinks' => $this->typeLinks($selectedType),
            'filters' => $request->only(['q']),
            'option' => new WorldCupCustomizationOption([
                'category_key' => $selectedType->categoryKey(),
                'type' => $selectedType,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function create(): View
    {
        return view('admin.world-cup-customization-options.create', [
            'option' => new WorldCupCustomizationOption([
                'category_key' => 'drawstring',
                'type' => WorldCupCustomizationType::DrawstringMaterialsOption,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => WorldCupCustomizationType::options(),
        ]);
    }

    public function store(WorldCupCustomizationOptionRequest $request): RedirectResponse
    {
        $option = $this->optionService->create($request);
        $type = $option->type instanceof WorldCupCustomizationType ? $option->type->value : (string) $option->type;

        if ($request->boolean('_return_to_type')) {
            return redirect()->route('admin.world-cup-customization-options.type', $type)
                ->with('status', $option->name.' created successfully.');
        }

        return redirect()->route('admin.world-cup-customization-options.edit', $option)
            ->with('status', 'World Cup customization option created successfully.');
    }

    public function edit(WorldCupCustomizationOption $worldCupCustomizationOption): View
    {
        $worldCupCustomizationOption->load('images');
        return view('admin.world-cup-customization-options.edit', [
            'option' => $worldCupCustomizationOption,
            'types' => WorldCupCustomizationType::options(),
        ]);
    }

    public function update(
        WorldCupCustomizationOptionRequest $request,
        WorldCupCustomizationOption $worldCupCustomizationOption
    ): RedirectResponse {
        $option = $this->optionService->update($worldCupCustomizationOption, $request);
        $type = $option->type instanceof WorldCupCustomizationType ? $option->type->value : (string) $option->type;

        if ($request->boolean('_return_to_type')) {
            return redirect()->route('admin.world-cup-customization-options.type', $type)
                ->with('status', $option->name.' updated successfully.');
        }

        return redirect()->route('admin.world-cup-customization-options.edit', $option)
            ->with('status', 'World Cup customization option updated successfully.');
    }

    public function destroy(WorldCupCustomizationOption $worldCupCustomizationOption): RedirectResponse
    {
        $type = $worldCupCustomizationOption->type instanceof WorldCupCustomizationType
            ? $worldCupCustomizationOption->type->value
            : (string) $worldCupCustomizationOption->type;
        $this->optionService->delete($worldCupCustomizationOption);

        return redirect()->route(
            'admin.world-cup-customization-options.type',
            $type ?: WorldCupCustomizationType::DrawstringMaterialsOption->value
        )->with('status', 'World Cup customization option deleted successfully.');
    }

    private function resolveType(string $type): WorldCupCustomizationType
    {
        $selectedType = WorldCupCustomizationType::tryFrom($type);
        abort_if($selectedType === null, 404);
        return $selectedType;
    }

    /** @return array<int,array{number:string,type:WorldCupCustomizationType,label:string}> */
    private function typeLinks(WorldCupCustomizationType $selectedType): array
    {
        return collect(WorldCupCustomizationRegistry::typesForCategory($selectedType->categoryKey()))
            ->map(static fn (WorldCupCustomizationType $type): array => [
                'number' => $type->menuNumber(),
                'type' => $type,
                'label' => $type->label(),
            ])->values()->all();
    }
}
