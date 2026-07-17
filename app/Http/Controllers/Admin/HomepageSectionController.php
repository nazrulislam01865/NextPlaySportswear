<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSectionRequest;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Services\Catalog\HomepageSectionMediaService;
use App\Services\Storefront\HomepageSectionService;
use App\Support\HomepageSectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    public function __construct(
        private readonly HomepageSectionMediaService $media,
        private readonly HomepageSectionService $sections,
    ) {
    }

    public function index(): View
    {
        HomepageSectionRegistry::ensureRows(auth('admin')->id());

        $sections = HomepageSection::query()
            ->whereNotIn('key', HomepageSectionRegistry::retiredKeys())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HomepageSection $section): array => HomepageSectionRegistry::mergeForView((string) $section->key, $section));

        return view('admin.homepage-sections.index', ['sections' => $sections]);
    }

    public function edit(string $key): View
    {
        $homepageSection = $this->sectionForKey($key);
        $definition = HomepageSectionRegistry::definition((string) $homepageSection->key);

        abort_unless($definition !== null, 404);

        return view('admin.homepage-sections.edit', [
            'section' => $homepageSection,
            'definition' => $definition,
            'viewSection' => HomepageSectionRegistry::mergeForView((string) $homepageSection->key, $homepageSection),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(HomepageSectionRequest $request, string $key): RedirectResponse
    {
        $homepageSection = $this->sectionForKey($key);

        DB::transaction(function () use ($request, $homepageSection): void {
            $payload = $request->payload();
            $payload['updated_by'] = $request->user()->id;

            $homepageSection->update($payload);
            $this->media->sync($homepageSection, $request);
        });

        $this->sections->flushCache();

        return redirect()
            ->route('admin.homepage.sections.edit', $homepageSection->key)
            ->with('status', 'Homepage section updated successfully.');
    }


    /** @return array<int, array{id:int,label:string,display_label:string,level:string,level_label:string,type:string,parent_id:int|null,depth:int}> */
    private function categoryOptions(): array
    {
        if (! class_exists(Category::class)) {
            return [];
        }

        $categories = Category::query()
            ->active()
            ->with(['parent.parent.parent'])
            ->orderBy('tree_path')
            ->ordered()
            ->get(['id', 'parent_id', 'name', 'short_title', 'category_type', 'depth', 'tree_path', 'sort_order']);

        if ($categories->isEmpty()) {
            return [];
        }

        return $categories
            ->sortBy([
                fn (Category $category): string => (string) ($category->tree_path ?: str_pad((string) $category->id, 8, '0', STR_PAD_LEFT)),
                fn (Category $category): int => (int) ($category->sort_order ?? 0),
                fn (Category $category): string => (string) $category->name,
            ])
            ->map(function (Category $category): array {
                $depth = max(0, (int) ($category->depth ?? ($category->parent_id ? 1 : 0)));
                $level = match (true) {
                    $depth <= 0 => 'category',
                    $depth === 1 => 'subcategory',
                    default => 'sub_subcategory',
                };
                $levelLabel = match ($level) {
                    'category' => 'Category',
                    'subcategory' => 'Subcategory',
                    default => 'Sub-subcategory',
                };

                $name = (string) ($category->short_title ?: $category->name);
                $prefix = $depth > 0 ? str_repeat('— ', min($depth, 5)) : '';
                $path = $this->categoryPath($category);

                return [
                    'id' => (int) $category->id,
                    'parent_id' => $category->parent_id ? (int) $category->parent_id : null,
                    'label' => $prefix.$name,
                    'display_label' => $name,
                    'path' => $path,
                    'level' => $level,
                    'level_label' => $levelLabel,
                    'type' => $levelLabel,
                    'depth' => $depth,
                ];
            })
            ->values()
            ->all();
    }

    private function categoryPath(Category $category): string
    {
        $chain = [];
        $current = $category;

        while ($current instanceof Category) {
            array_unshift($chain, (string) ($current->short_title ?: $current->name));
            $current = $current->parent;
        }

        return implode(' › ', array_filter($chain));
    }

    private function sectionForKey(string $key): HomepageSection
    {
        abort_if(HomepageSectionRegistry::isRetired($key), 404);

        HomepageSectionRegistry::ensureRows(auth('admin')->id());

        return HomepageSection::query()
            ->whereNotIn('key', HomepageSectionRegistry::retiredKeys())
            ->where('key', $key)
            ->firstOrFail();
    }
}
