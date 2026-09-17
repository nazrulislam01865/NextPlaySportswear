<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class SearchSuggestionResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'products' => collect((array) ($this->resource['products'] ?? []))
                ->map(function (mixed $product) use ($request): array {
                    $item = (new ProductCardResource($product))->resolve($request);
                    $item['type'] = 'product';

                    return $item;
                })->values()->all(),
            'categories' => collect((array) ($this->resource['categories'] ?? []))
                ->map(function (mixed $category) use ($request): array {
                    $item = (new CategoryResource($category))->resolve($request);
                    $item['type'] = 'category';

                    return $item;
                })->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'query' => (string) ($this->resource['query'] ?? ''),
                'product_count' => count((array) ($this->resource['products'] ?? [])),
                'category_count' => count((array) ($this->resource['categories'] ?? [])),
                'catalog_version' => (string) ($this->resource['catalog_version'] ?? ''),
            ],
            'request_id' => RequestId::from($request),
        ];
    }
}
