<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Services\Cart\CartService;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $product = $this->products->findBySlug((string) $payload['product_slug']);

        abort_if($product === null, 404);

        $artworkSettings = $product['artwork_upload'] ?? ['enabled' => false];
        $artworkFiles = collect((array) $request->file('artwork_files', []));
        if ($request->hasFile('artwork_file')) {
            $artworkFiles->push($request->file('artwork_file'));
        }
        $artworkFiles = $artworkFiles->filter()->values();

        if (! ($artworkSettings['enabled'] ?? false) && $artworkFiles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Custom artwork uploads are not enabled for this product.',
            ]);
        }

        $maximumFiles = max(1, min(12, (int) ($artworkSettings['max_files'] ?? 5)));
        $maximumSizeMb = max(1, min(25, (int) ($artworkSettings['max_file_size_mb'] ?? 15)));
        $acceptedTypes = collect($artworkSettings['accepted_types'] ?? ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'])
            ->map(fn ($type) => strtolower(ltrim(trim((string) $type), '.')))
            ->filter(fn ($type) => in_array($type, ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'], true))
            ->unique()
            ->values();

        if ($artworkFiles->count() > $maximumFiles) {
            throw ValidationException::withMessages([
                'artwork_files' => "You may upload a maximum of {$maximumFiles} artwork files for this product.",
            ]);
        }

        foreach ($artworkFiles as $file) {
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

        if (($artworkSettings['enabled'] ?? false) && ($artworkSettings['required'] ?? false) && $artworkFiles->isEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Upload at least one custom artwork file for this product.',
            ]);
        }

        $storedArtwork = $artworkFiles->map(function ($file) use ($request): array {
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
        })->values()->all();

        $payload['artwork_files'] = $storedArtwork;
        $payload['artwork_path'] = $storedArtwork[0]['path'] ?? null;
        $payload['artwork_original_name'] = $storedArtwork[0]['original_name'] ?? null;

        $this->cart->store($payload);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Product added to cart. Final totals are calculated securely on the server.');
    }

    public function update(UpdateCartItemRequest $request, string $cartItem): RedirectResponse
    {
        $this->cart->update($cartItem, (int) $request->validated('quantity'));

        return redirect()
            ->route('cart.index')
            ->with('status', 'Cart quantity updated.');
    }

    public function destroy(string $cartItem): RedirectResponse
    {
        $this->cart->remove($cartItem);

        return redirect()
            ->route('cart.index')
            ->with('status', 'Item removed from cart.');
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        $this->cart->applyCoupon($request->validated('coupon_code'));

        return redirect()
            ->route('cart.index')
            ->with('status', 'Coupon checked and applied if eligible.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cart->removeCoupon();

        return redirect()
            ->route('cart.index')
            ->with('status', 'Coupon removed.');
    }
}
