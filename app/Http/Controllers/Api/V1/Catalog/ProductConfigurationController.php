<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ProductPricePreviewRequest;
use App\Http\Resources\Api\V1\Catalog\ProductConfigurationResource;
use App\Http\Resources\Api\V1\Catalog\ProductPricePreviewResource;
use App\Services\Catalog\ProductConfigurationService;

final class ProductConfigurationController extends Controller
{
    public function show(string $slug, ProductConfigurationService $configurations): ProductConfigurationResource
    {
        $configuration = $configurations->configuration($slug);
        abort_if($configuration === null, 404);

        return new ProductConfigurationResource($configuration);
    }

    public function preview(
        ProductPricePreviewRequest $request,
        string $slug,
        ProductConfigurationService $configurations,
    ): ProductPricePreviewResource {
        $preview = $configurations->preview($slug, $request->previewPayload($slug));
        abort_if($preview === null, 404);

        return new ProductPricePreviewResource($preview);
    }
}
