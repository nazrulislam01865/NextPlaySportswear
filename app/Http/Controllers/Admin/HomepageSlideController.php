<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSlideRequest;
use App\Models\HomepageSlide;
use App\Services\Catalog\HomepageSlideMediaService;
use App\Services\Storefront\HomepageSliderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomepageSlideController extends Controller
{
    public function __construct(
        private readonly HomepageSlideMediaService $media,
        private readonly HomepageSliderService $slider,
    ) {
    }

    public function index(): View
    {
        return view('admin.homepage-slides.index', [
            'slides' => HomepageSlide::query()
                ->with(['creator:id,name', 'updater:id,name'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.homepage-slides.create', [
            'slide' => new HomepageSlide([
                'show_content' => true,
                'show_eyebrow' => true,
                'show_title' => true,
                'show_description' => true,
                'show_primary_button' => true,
                'show_secondary_button' => false,
                'image_focal_position' => 'center',
                'content_position' => 'left',
                'text_alignment' => 'left',
                'text_theme' => 'light',
                'overlay_color' => '#0D2545',
                'overlay_opacity' => 72,
                'is_active' => true,
                'sort_order' => (int) HomepageSlide::query()->max('sort_order') + 10,
                'primary_target' => '_self',
                'secondary_target' => '_self',
            ]),
        ]);
    }

    public function store(HomepageSlideRequest $request): RedirectResponse
    {
        $slide = DB::transaction(function () use ($request): HomepageSlide {
            $payload = $this->payload($request);
            $payload['created_by'] = $request->user()->id;
            $payload['updated_by'] = $request->user()->id;

            $slide = HomepageSlide::query()->create($payload);
            $this->media->sync($slide, $request);

            return $slide;
        });

        $this->slider->flushCache();

        return redirect()
            ->route('admin.homepage-slides.edit', $slide)
            ->with('status', 'Homepage slide created successfully.');
    }

    public function edit(HomepageSlide $homepageSlide): View
    {
        return view('admin.homepage-slides.edit', ['slide' => $homepageSlide]);
    }

    public function update(HomepageSlideRequest $request, HomepageSlide $homepageSlide): RedirectResponse
    {
        DB::transaction(function () use ($request, $homepageSlide): void {
            $payload = $this->payload($request);
            $payload['updated_by'] = $request->user()->id;

            $homepageSlide->update($payload);
            $this->media->sync($homepageSlide, $request);
        });

        $this->slider->flushCache();

        return redirect()
            ->route('admin.homepage-slides.edit', $homepageSlide)
            ->with('status', 'Homepage slide updated successfully.');
    }

    public function toggle(HomepageSlide $homepageSlide): RedirectResponse
    {
        $homepageSlide->forceFill([
            'is_active' => ! $homepageSlide->is_active,
            'updated_by' => auth()->id(),
        ])->save();

        $this->slider->flushCache();

        return back()->with('status', $homepageSlide->is_active ? 'Slide activated.' : 'Slide deactivated.');
    }

    public function destroy(HomepageSlide $homepageSlide): RedirectResponse
    {
        DB::transaction(function () use ($homepageSlide): void {
            $this->media->deleteAll($homepageSlide);
            $homepageSlide->delete();
        });

        $this->slider->flushCache();

        return redirect()
            ->route('admin.homepage-slides.index')
            ->with('status', 'Homepage slide deleted.');
    }

    /** @return array<string, mixed> */
    private function payload(HomepageSlideRequest $request): array
    {
        $payload = Arr::except($request->validated(), [
            'image_file', 'remove_image',
        ]);

        foreach ([
            'eyebrow', 'title', 'description', 'image_alt', 'primary_label',
            'primary_url', 'secondary_label', 'secondary_url', 'starts_at', 'ends_at',
        ] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim(strip_tags($payload[$field])) ?: null;
            }
        }

        unset($payload['image_url']); // Media service owns image source changes.

        return $payload;
    }
}
