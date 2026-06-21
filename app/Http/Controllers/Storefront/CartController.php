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
        $this->cart->store($request->validated());

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
