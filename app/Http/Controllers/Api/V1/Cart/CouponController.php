<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\CouponApplyRequest;
use App\Http\Resources\Api\V1\Cart\CartResource;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;

final class CouponController extends Controller
{
    public function store(CouponApplyRequest $request, CartService $cart): JsonResponse
    {
        $summary = $cart->applyCoupon((string) $request->validated('coupon_code'));
        $applied = filled($summary['coupon_code'] ?? null) && blank($summary['coupon_error'] ?? null);

        return (new CartResource($summary))->response()->setStatusCode($applied ? 200 : 422);
    }

    public function destroy(CartService $cart): CartResource
    {
        return new CartResource($cart->removeCoupon());
    }
}
