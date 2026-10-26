<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibraryImage;
use App\Services\Catalog\StoredPublicImage;
use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MediaLibraryController extends Controller
{
    private const CATEGORIES = ['general', 'products', 'branding', 'banners'];

    public function index(Request $request): JsonResponse|View
    {
        if (! $this->shouldReturnJson($request)) {
            return view('admin.media-library.index');
        }

        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'in:all,images,documents,videos,others'],
            'date' => ['nullable', 'in:all,today,week,month'],
            'category' => ['nullable', 'in:all,general,products,branding,banners'],
            'purpose' => ['nullable', 'in:product'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $perPage = max(1, min(48, (int) $request->query('per_page', 16)));
        $images = $this->galleryQuery($request)->paginate($perPage);

        return response()->json([
            'data' => $images->getCollection()->map(fn (MediaLibraryImage $image): array => $this->imagePayload($image))->values(),
            'meta' => [
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
                'per_page' => $images->perPage(),
                'total' => $images->total(),
                'from' => $images->firstItem(),
                'to' => $images->lastItem(),
                'has_more' => $images->hasMorePages(),
                'next_page' => $images->hasMorePages() ? $images->currentPage() + 1 : null,
            ],
        ])->withHeaders(['Cache-Control' => 'no-store, max-age=0']);
    }

    public function upload(): View
    {
        // The prototype shows only the files selected in the browser. No gallery
        // statistics or image queries are needed just to render the upload page.
        return view('admin.media-library.upload');
    }

    public function store(Request $request): JsonResponse
    {
        // Preserve the image-only product picker, including its existing 5 MB
        // image limit. The standalone upload screen sends files[] with metadata.
        $field = $request->hasFile('files') ? 'files' : 'images';
        $isStandaloneUpload = $field === 'files';
        $rules = [
            $field => ['required', 'array', 'min:1', 'max:20'],
            $field.'.*' => $isStandaloneUpload
                ? ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,pdf,doc,docx,xls,xlsx,txt,mp4,mov,webm', 'max:20480']
                : ['required', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'category' => ['nullable', 'in:general,products,branding,banners'],
        ];
        if ($isStandaloneUpload) {
            $rules += [
                'metadata' => ['sometimes', 'array', 'max:20'],
                'metadata.*' => ['array'],
                'metadata.*.title' => ['nullable', 'string', 'max:250'],
                'metadata.*.alt_text' => ['nullable', 'string', 'max:255'],
                'metadata.*.caption' => ['nullable', 'string', 'max:255'],
                'metadata.*.description' => ['nullable', 'string', 'max:2000'],
            ];
        }
        $validated = $request->validate($rules);
        $uploaded = $request->file($field, []);
        $metadata = $isStandaloneUpload ? ($validated['metadata'] ?? []) : [];
        $savedPaths = [];
        try {
            $images = DB::transaction(function () use ($uploaded, $metadata, $field, $request, &$savedPaths): array {
                $results = [];
                $directory = 'media-library/'.now()->format('Y/m');
                foreach ($uploaded as $index => $file) {
                    $originalFilename = Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, '');
                    $originalName = Str::limit(pathinfo($originalFilename, PATHINFO_FILENAME) ?: 'Media file', 250, '');
                    $details = $metadata[$index] ?? [];
                    $title = trim((string) ($details['title'] ?? ''));
                    $name = $title !== '' ? $title : $originalName;
                    $mime = (string) $file->getMimeType();
                    $isImage = str_starts_with($mime, 'image/');
                    $dimensions = $isImage ? (@getimagesize($file->getRealPath()) ?: []) : [];

                    $path = StoredPublicImage::replace($file, $directory, null);
                    $savedPaths[] = $path;
                    $image = MediaLibraryImage::query()->create([
                        'path' => $path,
                        'name' => $name,
                        'original_filename' => $originalFilename,
                        'alt_text' => $details['alt_text'] ?? ($isImage ? $name : null),
                        'caption' => $details['caption'] ?? null,
                        'description' => $details['description'] ?? null,
                        'mime_type' => $mime,
                        'size_bytes' => $file->getSize(),
                        'width' => $dimensions[0] ?? null,
                        'height' => $dimensions[1] ?? null,
                        'category' => $request->input('category') ?: ($field === 'images' ? 'products' : 'general'),
                        'source' => 'upload',
                        'created_by' => auth('admin')->id(),
                    ]);
                    $results[] = $this->imagePayload($image);
                }
                return $results;
            });
        } catch (\Throwable $exception) {
            // A failed batch must not leave orphaned files from earlier entries.
            if ($savedPaths) Storage::disk('public')->delete($savedPaths);
            throw $exception;
        }

        return response()->json([
            'data' => $images,
            'message' => count($images).' file'.(count($images) === 1 ? '' : 's').' added to the gallery.',
        ], 201);
    }

    public function update(Request $request, MediaLibraryImage $media): JsonResponse
    {
        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'in:general,products,branding,banners'],
        ]);
        $media->update($data);

        return response()->json(['message' => 'Media details saved.', 'data' => $this->imagePayload($media->refresh())]);
    }

    public function destroy(MediaLibraryImage $media): JsonResponse
    {
        // Deleting a referenced library record would silently detach product photos.
        if ($media->productImages()->exists()) {
            return response()->json([
                'message' => 'This image is used by a product. Remove it from the product before deleting it from the gallery.',
            ], 409);
        }

        // Other content may use the stored URL directly. Preserve the underlying
        // asset when removing its gallery record so existing storefront URLs work.
        $media->delete();

        return response()->json(['message' => 'File removed from the media gallery.']);
    }

    public function download(MediaLibraryImage $media): BinaryFileResponse|Response
    {
        $path = $this->storedFilePath($media);
        if ($path !== null) {
            return response()->download(Storage::disk('public')->path($path), $media->filename(), [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $url = $media->publicUrl();
        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return redirect()->away($url);
        }

        abort(404, 'Media file is unavailable.');
    }

    public function preview(MediaLibraryImage $media): BinaryFileResponse
    {
        $path = $this->storedFilePath($media);
        if ($path === null || $media->type() !== 'videos') {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function storedFilePath(MediaLibraryImage $media): ?string
    {
        $path = filled($media->path) ? PublicMedia::normalizePath((string) $media->path) : '';
        return $path !== '' && Storage::disk('public')->exists($path) ? $path : null;
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->wantsJson() || $request->ajax()
            || $request->query->has('page') || $request->query->has('q');
    }

    private function galleryQuery(Request $request): Builder
    {
        $query = MediaLibraryImage::query()
            ->where(function (Builder $builder): void {
                $builder->whereNotNull('path')->where('path', '<>', '')
                    ->where('path', 'not like', '%product-placeholder.svg%')
                    ->orWhere(function (Builder $source): void {
                        $source->whereNotNull('url')->where('url', '<>', '')
                            ->where('url', 'not like', '%product-placeholder.svg%');
                    });
            });

        $type = $request->query('purpose') === 'product' ? 'images' : $request->query('type', 'all');
        if ($type === 'images') {
            $query->where(fn (Builder $q) => $q->whereNull('mime_type')->orWhere('mime_type', 'like', 'image/%'));
        } elseif ($type === 'videos') {
            $query->where('mime_type', 'like', 'video/%');
        } elseif ($type === 'documents') {
            $query->whereIn('mime_type', [
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ]);
        } elseif ($type === 'others') {
            $query->whereNotNull('mime_type')
                ->where('mime_type', 'not like', 'image/%')
                ->where('mime_type', 'not like', 'video/%')
                ->whereNotIn('mime_type', [
                    'application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                ]);
        }

        $date = $request->query('date', 'all');
        $scope = $request->query('scope');
        if ($scope === 'today' || $date === 'today') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($date === 'week' || $scope === 'recent') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($date === 'month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        $category = (string) $request->query('category', 'all');
        if ($category === 'products') {
            $query->where(fn (Builder $q) => $q->where('category', 'products')
                ->orWhere(fn (Builder $q) => $q->whereNull('category')->where('source', 'product')));
        } elseif ($category === 'general') {
            $query->where(fn (Builder $q) => $q->where('category', 'general')
                ->orWhere(fn (Builder $q) => $q->whereNull('category')->where('source', '<>', 'product')));
        } elseif (in_array($category, self::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where('name', 'like', $like)
                    ->orWhere('original_filename', 'like', $like)
                    ->orWhere('alt_text', 'like', $like)
                    ->orWhere('caption', 'like', $like);
            });
        }

        return $query->orderByDesc('id');
    }

    private function imagePayload(MediaLibraryImage $media): array
    {
        $type = $media->type();
        $category = $media->category ?: ($media->source === 'product' ? 'products' : 'general');

        return [
            'id' => $media->id,
            'name' => $media->name,
            'filename' => $media->filename(),
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'description' => $media->description,
            'category' => $category,
            'type' => $type,
            'mime_type' => $media->mime_type,
            'type_label' => strtoupper(pathinfo($media->filename(), PATHINFO_EXTENSION) ?: ($type === 'images' ? 'IMAGE' : 'FILE')),
            'url' => $type === 'images' ? $media->publicUrl() : route('admin.media-library.download', $media),
            'preview_url' => $type === 'videos' ? route('admin.media-library.preview', $media) : null,
            'download_url' => route('admin.media-library.download', $media),
            'update_url' => route('admin.media-library.update', $media),
            'delete_url' => route('admin.media-library.destroy', $media),
            'size_label' => $media->sizeLabel(),
            'width' => $media->width,
            'height' => $media->height,
            'created_at' => $media->created_at?->format('M j, Y g:i A') ?? '',
            'created_at_iso' => $media->created_at?->toIso8601String(),
        ];
    }
}
