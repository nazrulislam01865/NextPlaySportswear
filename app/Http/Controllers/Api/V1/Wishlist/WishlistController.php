<?php

namespace App\Http\Controllers\Api\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wishlist\WishlistStoreRequest;
use App\Http\Resources\Api\V1\Wishlist\WishlistResource;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WishlistController extends Controller
{
    public function index(Request $request, WishlistService $wishlist): WishlistResource
    {
        return new WishlistResource($wishlist->summary($request->user('web')));
    }

    public function store(WishlistStoreRequest $request, WishlistService $wishlist): JsonResponse
    {
        $summary = $wishlist->add($request->user('web'), (int) $request->validated('product_id'));

        return (new WishlistResource($summary))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $product, WishlistService $wishlist): WishlistResource
    {
        return new WishlistResource($wishlist->remove($request->user('web'), $product));
    }
}
