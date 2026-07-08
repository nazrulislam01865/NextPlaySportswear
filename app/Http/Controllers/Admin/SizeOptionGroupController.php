<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JerseyCustomizationType;
use App\Enums\SizeAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SizeOptionGroupRequest;
use App\Models\SizeOptionGroup;
use App\Services\Catalog\SizeOptionGroupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SizeOptionGroupController extends Controller
{
    public function __construct(private readonly SizeOptionGroupService $service)
    {
    }

    public function index(Request $request): View
    {
        $query = SizeOptionGroup::query()->with('sizes')->withCount(['sizes', 'productGroups']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if ($request->filled('audience')) {
            $query->where('audience', $request->query('audience'));
        }

        return view('admin.size-option-groups.index', [
            'groups' => $query->ordered()->paginate(30)->withQueryString(),
            'audiences' => SizeAudience::options(),
            'filters' => $request->only(['q', 'audience']),
            'customizationContext' => $this->customizationContext($request),
        ]);
    }

    public function create(): View
    {
        return view('admin.size-option-groups.create', [
            'group' => new SizeOptionGroup(['audience' => SizeAudience::Unisex, 'is_active' => true]),
            'audiences' => SizeAudience::options(),
        ]);
    }

    public function store(SizeOptionGroupRequest $request): RedirectResponse
    {
        $group = $this->service->create($request);

        return redirect()->route('admin.size-option-groups.edit', $this->routeParameters($group, $request))
            ->with('status', 'Size option group created successfully.');
    }

    public function edit(SizeOptionGroup $sizeOptionGroup): View
    {
        $sizeOptionGroup->load('sizes');

        return view('admin.size-option-groups.edit', [
            'group' => $sizeOptionGroup,
            'audiences' => SizeAudience::options(),
        ]);
    }

    public function update(SizeOptionGroupRequest $request, SizeOptionGroup $sizeOptionGroup): RedirectResponse
    {
        $group = $this->service->update($sizeOptionGroup, $request);

        return redirect()->route('admin.size-option-groups.edit', $this->routeParameters($group, $request))
            ->with('status', 'Size option group updated successfully.');
    }

    public function destroy(Request $request, SizeOptionGroup $sizeOptionGroup): RedirectResponse
    {
        $this->service->delete($sizeOptionGroup);

        $context = $this->customizationContext($request);

        return redirect()->route(
            'admin.size-option-groups.index',
            $context ? ['customization' => $context] : []
        )->with('status', 'Size option group deleted successfully.');
    }

    /** @return array<int|string, mixed> */
    private function routeParameters(SizeOptionGroup $group, Request $request): array
    {
        $context = $this->customizationContext($request);

        return $context
            ? ['sizeOptionGroup' => $group, 'customization' => $context]
            : ['sizeOptionGroup' => $group];
    }

    private function customizationContext(Request $request): ?string
    {
        $context = (string) ($request->query('customization') ?: $request->input('_customization_context'));

        return array_key_exists($context, JerseyCustomizationType::menuGroups()) ? $context : null;
    }
}
