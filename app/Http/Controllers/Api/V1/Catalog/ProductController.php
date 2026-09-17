<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ProductIndexRequest;
use App\Http\Resources\Api\V1\Catalog\ProductCollectionResource;
use App\Http\Resources\Api\V1\Catalog\ProductResource;
use App\Services\Catalog\CatalogReadService;

final class ProductController extends Controller
{
    public function index(ProductIndexRequest $request, CatalogReadService $catalog): ProductCollectionResource
    {
        return new ProductCollectionResource($catalog->products($request->filters()));
    }

    public function show(string $slug, CatalogReadService $catalog): ProductResource
    {
        $product = $catalog->product($slug);

        abort_if($product === null, 404);

        return new ProductResource($product);
    }
}
