<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\CategoryShowRequest;
use App\Http\Resources\Api\V1\Catalog\CategoryCollectionResource;
use App\Http\Resources\Api\V1\Catalog\CategoryDetailResource;
use App\Services\Catalog\CatalogReadService;

final class CategoryController extends Controller
{
    public function index(CatalogReadService $catalog): CategoryCollectionResource
    {
        return new CategoryCollectionResource($catalog->categories());
    }

    public function show(CategoryShowRequest $request, string $slug, CatalogReadService $catalog): CategoryDetailResource
    {
        $category = $catalog->category(
            $slug,
            $request->filters(),
            $request->filled('sort'),
        );

        abort_if($category === null, 404);

        return new CategoryDetailResource($category);
    }
}
