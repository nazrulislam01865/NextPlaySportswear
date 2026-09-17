<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\CartItemOptionsRequest;
use App\Http\Requests\Api\V1\Cart\CartItemStoreRequest;
use App\Http\Requests\Api\V1\Cart\CartItemUpdateRequest;
use App\Http\Resources\Api\V1\Cart\CartResource;
use App\Services\Cart\CartArtworkService;
use App\Services\Cart\CartMutationIdempotencyService;
use App\Services\Cart\CartService;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CartItemController extends Controller
{
    public function store(
        CartItemStoreRequest $request,
        CartService $cart,
        ProductCatalogService $products,
        CartArtworkService $artwork,
        CartMutationIdempotencyService $idempotency,
    ): JsonResponse {
        $payload = $request->validated();
        $fingerprintPayload = $this->idempotencyPayload($request, $payload);
        $replayed = $idempotency->replay($request, 'cart.items.store', $fingerprintPayload);
        if ($replayed !== null) {
            return (new CartResource($replayed))->response()
                ->header('Idempotency-Replayed', 'true');
        }

        $product = $products->findFullBySlug((string) $payload['product_slug']);
        abort_if($product === null, 404);

        $preparedArtwork = $artwork->prepare($request, $product);
        $payload['artwork_files'] = $preparedArtwork['files'];
        $payload['artwork_path'] = $preparedArtwork['files'][0]['path'] ?? null;
        $payload['artwork_original_name'] = $preparedArtwork['files'][0]['original_name'] ?? null;

        try {
            $summary = $cart->store($payload);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($preparedArtwork['new_paths']);
            throw $exception;
        }

        $idempotency->remember($request, 'cart.items.store', $fingerprintPayload, $summary);

        return (new CartResource($summary))->response()->setStatusCode(201);
    }

    public function update(CartItemUpdateRequest $request, string $item, CartService $cart): CartResource
    {
        return new CartResource($cart->update($item, (int) $request->validated('quantity')));
    }

    public function updateOptions(
        CartItemOptionsRequest $request,
        string $item,
        CartService $cart,
        ProductCatalogService $products,
        CartArtworkService $artwork,
        CartMutationIdempotencyService $idempotency,
    ): JsonResponse {
        $payload = $request->validated();
        $fingerprintPayload = $this->idempotencyPayload($request, $payload);
        $operation = 'cart.items.options.'.$item;
        $replayed = $idempotency->replay($request, $operation, $fingerprintPayload);
        if ($replayed !== null) {
            return (new CartResource($replayed))->response()
                ->header('Idempotency-Replayed', 'true');
        }

        $existing = $cart->findItem($item);
        abort_if($existing === null, 404, 'The cart item you are trying to edit no longer exists.');

        $slug = (string) ($existing['product_slug'] ?? '');
        abort_unless(hash_equals($slug, (string) ($payload['product_slug'] ?? '')), 422, 'The selected product does not match this cart item.');

        $product = $products->findFullBySlug($slug);
        abort_if($product === null, 404);

        $existingArtwork = (array) data_get($existing, 'customization.artwork_files', []);
        $preparedArtwork = $artwork->prepare($request, $product, $existingArtwork);
        $payload['artwork_files'] = $preparedArtwork['files'];
        $payload['artwork_path'] = $preparedArtwork['files'][0]['path'] ?? null;
        $payload['artwork_original_name'] = $preparedArtwork['files'][0]['original_name'] ?? null;

        try {
            $summary = $cart->replace($item, $payload);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($preparedArtwork['new_paths']);
            throw $exception;
        }

        $idempotency->remember($request, $operation, $fingerprintPayload, $summary);

        return (new CartResource($summary))->response();
    }

    public function destroy(string $item, CartService $cart): CartResource
    {
        return new CartResource($cart->remove($item));
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function idempotencyPayload(CartItemStoreRequest|CartItemOptionsRequest $request, array $payload): array
    {
        $fingerprint = Arr::except($payload, ['artwork_files', 'artwork_file']);
        $uploads = collect((array) $request->file('artwork_files', []));
        if ($request->hasFile('artwork_file')) {
            $uploads->push($request->file('artwork_file'));
        }

        $fingerprint['_artwork'] = $uploads->filter()->map(fn ($file): array => [
            'name' => (string) $file->getClientOriginalName(),
            'size' => (int) $file->getSize(),
            'mime' => (string) ($file->getMimeType() ?: ''),
        ])->values()->all();

        return $fingerprint;
    }
}
