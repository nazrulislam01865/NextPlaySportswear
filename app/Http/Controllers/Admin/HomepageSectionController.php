<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSectionRequest;
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

    private function sectionForKey(string $key): HomepageSection
    {
        HomepageSectionRegistry::ensureRows(auth('admin')->id());

        return HomepageSection::query()->where('key', $key)->firstOrFail();
    }
}
