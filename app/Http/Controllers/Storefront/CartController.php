<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\UpdateCartItemOptionsRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Services\Cart\CartService;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ProductCatalogService $products,
    ) {
    }

    public function index(Request $request): View
    {
        $isPreview = $request->boolean('preview') || ($request->has('id') && $this->cart->summary()['is_empty']);
        $cart = $this->cart->summary($isPreview);
        $recommendedProducts = collect($this->products->featured())->take(4)->values()->all();

        return view('storefront.cart.index', [
            'cart' => $cart,
            'recommendedProducts' => $recommendedProducts,
            'seo' => [
                'title' => 'Shopping Cart | NextPlay Sportswear',
                'description' => 'Review your selected custom sportswear products, quantities, artwork notes, proof support, discounts, shipping estimate, and order total.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function showArtwork(string $cartItem, int $artworkIndex): StreamedResponse
    {
        $item = $this->cart->findItem($cartItem);

        abort_if($item === null, 404, 'The cart artwork you requested is no longer available.');

        $artworkFiles = collect((array) data_get($item, 'customization.artwork_files', []))
            ->filter(fn ($file): bool => is_array($file) && filled($file['path'] ?? null))
            ->values()
            ->all();
        $artwork = $artworkFiles[$artworkIndex] ?? null;

        abort_unless(is_array($artwork) && filled($artwork['path'] ?? null), 404);

        $path = (string) $artwork['path'];
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $originalName = basename((string) ($artwork['original_name'] ?? 'artwork-file'));
        $safeName = str_replace(["\r", "\n", '"'], '', $originalName);
        $mimeType = trim((string) ($artwork['mime_type'] ?? ''));
        if ($mimeType === '') {
            $mimeType = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        }
        $stream = $disk->readStream($path);

        abort_if($stream === false, 404);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:",
        ]);
    }

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $product = $this->products->findBySlug((string) $payload['product_slug']);

        abort_if($product === null, 404);

        $artwork = $this->prepareArtworkPayload($request, $product);
        $payload['artwork_files'] = $artwork['files'];
        $payload['artwork_path'] = $artwork['files'][0]['path'] ?? null;
        $payload['artwork_original_name'] = $artwork['files'][0]['original_name'] ?? null;

        try {
            $this->cart->store($payload);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($artwork['new_paths']);
            throw $exception;
        }

        return redirect()
            ->route('cart.index')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Added',
                'message' => 'Your order has been added to your shopping cart.',
                'key' => 'cart-added',
                'duration' => 4200,
            ]);
    }

    public function updateOptions(UpdateCartItemOptionsRequest $request, string $cartItem): RedirectResponse
    {
        $existingItem = $this->cart->findItem($cartItem);

        abort_if($existingItem === null, 404, 'The cart item you are trying to edit no longer exists.');

        $payload = $request->validated();
        $productSlug = (string) ($existingItem['product_slug'] ?? '');

        abort_unless(hash_equals($productSlug, (string) ($payload['product_slug'] ?? '')), 422, 'The selected product does not match this cart item.');

        $product = $this->products->findBySlug($productSlug);
        abort_if($product === null, 404);

        $existingArtwork = (array) data_get($existingItem, 'customization.artwork_files', []);
        $artwork = $this->prepareArtworkPayload($request, $product, $existingArtwork);
        $payload['artwork_files'] = $artwork['files'];
        $payload['artwork_path'] = $artwork['files'][0]['path'] ?? null;
        $payload['artwork_original_name'] = $artwork['files'][0]['original_name'] ?? null;

        try {
            $this->cart->replace($cartItem, $payload);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($artwork['new_paths']);
            throw $exception;
        }

        return redirect()
            ->route('cart.index')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Updated',
                'message' => 'Your product options have been updated in the cart.',
                'key' => 'cart-item-updated',
                'duration' => 4200,
            ]);
    }

    public function update(UpdateCartItemRequest $request, string $cartItem): RedirectResponse|JsonResponse
    {
        $summary = $this->cart->update($cartItem, (int) $request->validated('quantity'));

        if ($request->expectsJson()) {
            $updatedItem = collect($summary['items'] ?? [])->firstWhere('key', $cartItem);

            return response()->json([
                'success' => true,
                'message' => 'Cart quantity updated.',
                'cart' => $summary,
                'item' => $updatedItem,
                'item_html' => $updatedItem
                    ? Blade::render('<x-storefront.cart.item-card :item="$item" />', ['item' => $updatedItem])
                    : null,
            ]);
        }

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart quantity updated.');
    }

    public function destroy(Request $request, string $cartItem): RedirectResponse|JsonResponse
    {
        $summary = $this->cart->remove($cartItem);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart' => $summary,
                'removed_key' => $cartItem,
            ]);
        }

        return redirect()
            ->route('cart.index')
            ->with('status', 'Item removed from cart.');
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse|JsonResponse
    {
        $summary = $this->cart->applyCoupon($request->validated('coupon_code'));
        $applied = filled($summary['coupon_code'] ?? null) && blank($summary['coupon_error'] ?? null);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $applied,
                'message' => $applied
                    ? ($summary['coupon_message'] ?? 'Promo code applied successfully.')
                    : ($summary['coupon_error'] ?? 'This promo code is not valid for the current cart.'),
                'cart' => $summary,
            ], $applied ? 200 : 422);
        }

        return redirect()
            ->route('cart.index')
            ->with($applied ? 'status' : 'coupon_error', $applied
                ? ($summary['coupon_message'] ?? 'Promo code applied successfully.')
                : ($summary['coupon_error'] ?? 'This promo code is not valid for the current cart.'));
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $summary = $this->cart->removeCoupon();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Promo code removed.',
                'cart' => $summary,
            ]);
        }

        return redirect()
            ->route('cart.index')
            ->with('status', 'Promo code removed.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $existingArtwork
     * @return array{files: array<int, array<string, mixed>>, new_paths: array<int, string>}
     */
    private function prepareArtworkPayload(Request $request, array $product, array $existingArtwork = []): array
    {
        $settings = $product['artwork_upload'] ?? ['enabled' => false];
        $enabled = (bool) ($settings['enabled'] ?? false);
        $maximumFiles = max(1, min(12, (int) ($settings['max_files'] ?? 5)));
        $maximumSizeMb = max(1, min(25, (int) ($settings['max_file_size_mb'] ?? 15)));
        $acceptedTypes = collect($settings['accepted_types'] ?? ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'])
            ->map(fn ($type) => strtolower(ltrim(trim((string) $type), '.')))
            ->filter(fn ($type) => in_array($type, ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'], true))
            ->unique()
            ->values();

        $existingByPath = collect($existingArtwork)
            ->filter(fn ($file): bool => is_array($file) && filled($file['path'] ?? null))
            ->map(fn (array $file): array => [
                'path' => (string) $file['path'],
                'original_name' => mb_substr((string) ($file['original_name'] ?? 'Artwork file'), 0, 255),
                'size' => max(0, (int) ($file['size'] ?? 0)),
                'mime_type' => mb_substr((string) ($file['mime_type'] ?? 'application/octet-stream'), 0, 120),
            ])
            ->keyBy('path');

        if (! $enabled) {
            $retainedArtwork = $existingByPath->values();
        } elseif ($request->has('retained_artwork_json')) {
            $retainedPaths = json_decode((string) $request->input('retained_artwork_json', '[]'), true);
            $retainedArtwork = collect(is_array($retainedPaths) ? $retainedPaths : [])
                ->map(fn ($path) => $existingByPath->get((string) $path))
                ->filter()
                ->unique('path')
                ->values();
        } else {
            $retainedArtwork = $existingByPath->values();
        }

        $uploads = collect((array) $request->file('artwork_files', []));
        if ($request->hasFile('artwork_file')) {
            $uploads->push($request->file('artwork_file'));
        }
        $uploads = $uploads->filter()->values();

        if (! $enabled && $uploads->isNotEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Custom artwork uploads are not enabled for this product.',
            ]);
        }

        if ($retainedArtwork->count() + $uploads->count() > $maximumFiles) {
            throw ValidationException::withMessages([
                'artwork_files' => "You may keep or upload a maximum of {$maximumFiles} artwork files for this product.",
            ]);
        }

        foreach ($uploads as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if (! $acceptedTypes->contains($extension)) {
                throw ValidationException::withMessages([
                    'artwork_files' => 'One or more artwork files use an unsupported file type.',
                ]);
            }
            if ((int) $file->getSize() > ($maximumSizeMb * 1024 * 1024)) {
                throw ValidationException::withMessages([
                    'artwork_files' => "Each artwork file must be no larger than {$maximumSizeMb} MB.",
                ]);
            }
        }

        if ($enabled && ($settings['required'] ?? false) && $retainedArtwork->isEmpty() && $uploads->isEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Upload or retain at least one custom artwork file for this product.',
            ]);
        }

        $storedArtwork = $uploads->map(function ($file) use ($request): array {
            $path = $file->store(
                'customer-artwork/'.hash('sha256', $request->session()->getId()),
                'local'
            );

            return [
                'path' => $path,
                'original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 255),
                'size' => (int) $file->getSize(),
                'mime_type' => mb_substr((string) ($file->getMimeType() ?: 'application/octet-stream'), 0, 120),
            ];
        })->values();

        $files = $retainedArtwork->concat($storedArtwork)->values()->all();

        return [
            'files' => $files,
            'new_paths' => $storedArtwork->pluck('path')->filter()->values()->all(),
        ];
    }
}
