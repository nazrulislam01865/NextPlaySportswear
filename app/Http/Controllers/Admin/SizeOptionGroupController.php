<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JerseyCustomizationType;
use App\Enums\SizeAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SizeOptionGroupRequest;
use App\Models\SizeOptionGroup;
use App\Support\ProductSizing;
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
        $context = $this->customizationContext($request) ?? 'jersey';
        $query = SizeOptionGroup::query()
            ->forCustomizationGroup($context)
            ->with('sizes')
            ->withCount(['sizes', 'productGroups']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if ($request->filled('audience')) {
            $query->where('audience', $request->query('audience'));
        }

        return view('admin.size-option-groups.index', [
            'groups' => $query->ordered()->paginate($this->adminPerPage(30))->withQueryString(),
            'audiences' => SizeAudience::options(),
            'filters' => $request->only(['q', 'audience']),
            'customizationContext' => $context,
            'customizationLabel' => $this->customizationLabel($context),
        ]);
    }

    public function create(Request $request): View
    {
        $context = $this->customizationContext($request) ?? 'jersey';

        return view('admin.size-option-groups.create', [
            'group' => new SizeOptionGroup([
                'customization_group' => $context,
                'audience' => SizeAudience::Unisex,
                'is_active' => true,
            ]),
            'audiences' => SizeAudience::options(),
            'customizationContext' => $context,
            'customizationLabel' => $this->customizationLabel($context),
        ]);
    }

    public function store(SizeOptionGroupRequest $request): RedirectResponse
    {
        $group = $this->service->create($request);

        return redirect()->route('admin.size-option-groups.edit', $this->routeParameters($group, $request))
            ->with('status', 'Size option group created successfully.');
    }

    public function edit(Request $request, SizeOptionGroup $sizeOptionGroup): View
    {
        $sizeOptionGroup->load('sizes');
        $context = (string) ($sizeOptionGroup->customization_group ?: 'jersey');

        return view('admin.size-option-groups.edit', [
            'group' => $sizeOptionGroup,
            'audiences' => SizeAudience::options(),
            'customizationContext' => $context,
            'customizationLabel' => $this->customizationLabel($context),
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
        $context = (string) ($sizeOptionGroup->customization_group ?: 'jersey');
        $this->service->delete($sizeOptionGroup);

        return redirect()->route('admin.size-option-groups.index', ['customization' => $context])
            ->with('status', 'Size option group deleted successfully.');
    }

    /** @return array<int|string, mixed> */
    private function routeParameters(SizeOptionGroup $group, Request $request): array
    {
        $context = (string) ($group->customization_group ?: 'jersey');

        return ['sizeOptionGroup' => $group, 'customization' => $context];
    }

    private function customizationContext(Request $request): ?string
    {
        $context = (string) ($request->query('customization') ?: $request->input('_customization_context') ?: $request->input('customization_group'));

        return array_key_exists($context, JerseyCustomizationType::menuGroups()) && ProductSizing::supportsMasterDataSizeOptions($context) ? $context : null;
    }

    private function customizationLabel(string $context): string
    {
        return JerseyCustomizationType::menuGroups()[$context]['label'] ?? 'Product Customization';
    }
}
