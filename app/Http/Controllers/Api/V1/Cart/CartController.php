<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Cart\CartResource;
use App\Services\Cart\CartService;

final class CartController extends Controller
{
    public function __invoke(CartService $cart): CartResource
    {
        return new CartResource($cart->summary());
    }
}
