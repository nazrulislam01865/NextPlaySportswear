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

    public function index(Request $request): View
    {
        $query = JerseyCustomizationOption::query()
            ->with('primaryImage')
            ->withCount('images');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        return view('admin.jersey-customization-options.index', [
            'options' => $query->ordered()->paginate(30)->withQueryString(),
            'types' => JerseyCustomizationType::options(),
            'filters' => $request->only(['q', 'type']),
        ]);
    }

    public function create(): View
    {
        return view('admin.jersey-customization-options.create', [
            'option' => new JerseyCustomizationOption([
                'type' => JerseyCustomizationType::NeckAndCollar,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => JerseyCustomizationType::options(),
        ]);
    }

    public function store(JerseyCustomizationOptionRequest $request): RedirectResponse
    {
        $option = $this->optionService->create($request);

        return redirect()
            ->route('admin.jersey-customization-options.edit', $option)
            ->with('status', 'Jersey customization option created successfully.');
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

        return redirect()
            ->route('admin.jersey-customization-options.edit', $option)
            ->with('status', 'Jersey customization option updated successfully.');
    }

    public function destroy(
        JerseyCustomizationOption $jerseyCustomizationOption
    ): RedirectResponse {
        $this->optionService->delete($jerseyCustomizationOption);

        return redirect()
            ->route('admin.jersey-customization-options.index')
            ->with('status', 'Jersey customization option deleted successfully.');
    }
}
