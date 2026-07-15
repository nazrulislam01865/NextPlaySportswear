<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibraryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = MediaLibraryImage::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        $images = $query->paginate(30);

        return response()->json([
            'data' => $images->getCollection()->map(fn (MediaLibraryImage $image): array => $this->imagePayload($image))->values(),
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

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
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

            return $this->imagePayload($image);
        })->values();

        return response()->json([
            'data' => $images,
            'message' => $images->count().' image'.($images->count() === 1 ? '' : 's').' added to the gallery.',
        ], 201);
    }

    private function imagePayload(MediaLibraryImage $image): array
    {
        return [
            'id' => $image->id,
            'name' => $image->name,
            'alt_text' => $image->alt_text,
            'url' => $image->publicUrl(),
            'size_label' => $image->sizeLabel(),
            'width' => $image->width,
            'height' => $image->height,
            'created_at' => optional($image->created_at)->format('M j, Y'),
        ];
    }
}
