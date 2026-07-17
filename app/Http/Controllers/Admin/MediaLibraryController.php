<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibraryImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $query = $this->galleryQuery($request);

        if (! $this->shouldReturnJson($request)) {
            return view('admin.media-library.index', [
                'totalImages' => (clone $query)->count(),
                'latestImage' => (clone $query)->latest('id')->first(),
            ]);
        }

        $perPage = max(6, min(48, (int) $request->query('per_page', 18)));
        $images = $query->paginate($perPage);
        $payloadImages = $images->getCollection()
            ->map(fn (MediaLibraryImage $image): ?array => $this->imagePayload($image))
            ->filter()
            ->values();

        return response()->json([
            'data' => $payloadImages,
            'meta' => [
                'current_page' => $images->currentPage(),
                'has_more' => $images->hasMorePages(),
                'next_page' => $images->hasMorePages() ? $images->currentPage() + 1 : null,
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }


    public function upload(Request $request): View
    {
        $query = $this->galleryQuery($request);
        $todayQuery = $this->galleryQuery($request->duplicate(query: array_merge($request->query(), ['scope' => 'today'])));

        return view('admin.media-library.upload', [
            'totalImages' => (clone $query)->count(),
            'todayImages' => (clone $todayQuery)->count(),
            'latestImage' => (clone $query)->latest('id')->first(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'mimetypes:image/jpeg,image/png,image/webp,image/avif', 'max:5120'],
        ]);

        $images = collect($request->file('images', []))->map(function ($uploadedImage): array {
            $path = $uploadedImage->store('media-library/'.now()->format('Y/m'), 'public');
            $originalName = pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Gallery image';
            $dimensions = @getimagesize($uploadedImage->getRealPath()) ?: [];

            $image = MediaLibraryImage::query()->create([
                'path' => $path,
                'name' => Str::limit($originalName, 250, ''),
                'alt_text' => $originalName,
                'mime_type' => $uploadedImage->getMimeType(),
                'size_bytes' => $uploadedImage->getSize(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'source' => 'upload',
                'created_by' => auth('admin')->id(),
            ]);

            return $this->imagePayload($image) ?? [
                'id' => $image->id,
                'name' => $image->name,
                'alt_text' => $image->alt_text,
                'url' => $image->publicUrl(),
                'size_label' => $image->sizeLabel(),
                'width' => $image->width,
                'height' => $image->height,
                'created_at' => optional($image->created_at)->format('M j, Y'),
            ];
        })->values();

        return response()->json([
            'data' => $images,
            'message' => $images->count().' image'.($images->count() === 1 ? '' : 's').' added to the gallery.',
        ], 201);
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || $request->query->has('page')
            || $request->query->has('q');
    }

    private function galleryQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('q', ''));

        $query = MediaLibraryImage::query()
            ->where(function (Builder $builder): void {
                $builder->where(function (Builder $source): void {
                    $source->whereNotNull('path')
                        ->whereRaw("TRIM(COALESCE(path, '')) <> ''")
                            ->where('path', 'not like', '%product-placeholder.svg%');
                })->orWhere(function (Builder $source): void {
                    $source->whereNotNull('url')
                        ->whereRaw("TRIM(COALESCE(url, '')) <> ''")
                        ->where('url', 'not like', '%product-placeholder.svg%');
                });
            })
            ->latest('id');

        $scope = Str::of((string) $request->query('scope', ''))->lower()->trim()->toString();

        if ($scope === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($scope === 'recent') {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function imagePayload(MediaLibraryImage $image): ?array
    {
        if (filled($image->path)) {
            $storedPath = PublicMedia::normalizePath((string) $image->path);
            if ($storedPath === '' || ! Storage::disk('public')->exists($storedPath)) {
                return null;
            }
        }

        $url = $image->publicUrl();

        if (! filled($url) || str_contains((string) $url, 'product-placeholder.svg')) {
            return null;
        }

        return [
            'id' => $image->id,
            'name' => $image->name,
            'alt_text' => $image->alt_text,
            'url' => $url,
            'size_label' => $image->sizeLabel(),
            'width' => $image->width,
            'height' => $image->height,
            'created_at' => optional($image->created_at)->format('M j, Y'),
        ];
    }
}
