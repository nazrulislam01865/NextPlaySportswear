<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Catalog\HomepageStagedUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class HomepageStagedUploadController extends Controller
{
    public function chunk(Request $request, HomepageStagedUploadService $uploads, string $uploadId, int $index): JsonResponse
    {
        $this->authorizeHomepageUpload($request);

        try {
            $uploads->storeChunk((int) $request->user()->id, $uploadId, $index, (string) $request->getContent());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function finalize(Request $request, HomepageStagedUploadService $uploads, string $uploadId): JsonResponse
    {
        $this->authorizeHomepageUpload($request);

        $validator = Validator::make($request->all(), [
            'original_name' => ['nullable', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.HomepageStagedUploadService::MAX_BYTES],
            'chunks' => ['required', 'integer', 'min:1', 'max:'.HomepageStagedUploadService::MAX_CHUNKS],
            'mime_type' => ['nullable', 'string', 'max:120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The image upload metadata is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $result = $uploads->finalize(
                (int) $request->user()->id,
                $uploadId,
                (int) $data['chunks'],
                (int) $data['size'],
                (string) ($data['original_name'] ?? ''),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    private function authorizeHomepageUpload(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->canAdmin('homepage_sections.manage') || $user->canAdmin('homepage_slides.manage')),
            403,
            'You do not have permission to upload homepage images.',
        );
    }
}
