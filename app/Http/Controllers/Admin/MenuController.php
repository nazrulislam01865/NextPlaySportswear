<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuFormRequest;
use App\Models\Menu;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly NavigationService $navigationService,
    ) {
    }

    public function index(): View
    {
        return view('admin.menus.index', [
            'menus' => Menu::query()->withCount('allItems')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formView('admin.menus.create', new Menu(['is_active' => true]));
    }

    public function store(MenuFormRequest $request): RedirectResponse
    {
        $menu = DB::transaction(function () use ($request): Menu {
            $menu = Menu::query()->create(Arr::only($request->validated(), ['name', 'slug', 'location', 'is_active']));
            $this->syncItems($menu, $request->validated('items', []));
            return $menu;
        });
        $this->navigationService->flushCache();

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Navigation menu created successfully.');
    }

    public function edit(Menu $menu): View
    {
        $menu->load(['allItems.category']);
        return $this->formView('admin.menus.edit', $menu);
    }

    public function update(MenuFormRequest $request, Menu $menu): RedirectResponse
    {
        DB::transaction(function () use ($request, $menu): void {
            $menu->update(Arr::only($request->validated(), ['name', 'slug', 'location', 'is_active']));
            $menu->allItems()->delete();
            $this->syncItems($menu, $request->validated('items', []));
        });
        $this->navigationService->flushCache();

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Navigation menu updated successfully.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        $this->navigationService->flushCache();
        return redirect()->route('admin.menus.index')->with('status', 'Navigation menu deleted.');
    }

    private function formView(string $view, Menu $menu): View
    {
        return view($view, [
            'menu' => $menu,
            'categories' => $this->treeService->flatOptions(),
        ]);
    }

    private function syncItems(Menu $menu, array $rows): void
    {
        $rows = collect($rows)->filter(fn ($row) => filled($row['label'] ?? null))->values();
        $created = [];
        $pending = $rows->all();
        $attempts = 0;

        while ($pending !== [] && $attempts < 8) {
            $attempts++;
            foreach ($pending as $position => $row) {
                $parentKey = $row['parent_key'] ?? null;
                if ($parentKey && ! isset($created[$parentKey])) {
                    continue;
                }

                $item = $menu->allItems()->create([
                    'parent_id' => $parentKey ? $created[$parentKey] : null,
                    'label' => trim((string) $row['label']),
                    'link_type' => $row['link_type'] ?? 'category',
                    'category_id' => ($row['link_type'] ?? '') === 'category' ? ($row['category_id'] ?? null) : null,
                    'route_name' => ($row['link_type'] ?? '') === 'route' ? ($row['route_name'] ?? null) : null,
                    'url' => ($row['link_type'] ?? '') === 'custom' ? ($row['url'] ?? null) : null,
                    'target' => $row['target'] ?? '_self',
                    'css_class' => $row['css_class'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'sort_order' => (int) ($row['sort_order'] ?? $position),
                ]);
                $created[$row['key']] = $item->id;
                unset($pending[$position]);
            }
        }

        if ($pending !== []) {
            throw new \RuntimeException('Menu items could not be ordered because their parent relationships are invalid.');
        }
    }
}
