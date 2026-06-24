<?php

namespace App\Http\Controllers\Admin;

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

        return redirect()->route('admin.size-option-groups.edit', $group)
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

        return redirect()->route('admin.size-option-groups.edit', $group)
            ->with('status', 'Size option group updated successfully.');
    }

    public function destroy(SizeOptionGroup $sizeOptionGroup): RedirectResponse
    {
        $this->service->delete($sizeOptionGroup);

        return redirect()->route('admin.size-option-groups.index')
            ->with('status', 'Size option group deleted successfully.');
    }
}
