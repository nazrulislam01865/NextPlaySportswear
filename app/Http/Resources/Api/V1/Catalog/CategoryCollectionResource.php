<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class CategoryCollectionResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'categories' => collect((array) ($this->resource['categories'] ?? []))
                ->map(fn (mixed $category): array => (new CategoryResource($category))->resolve($request))
                ->values()->all(),
            'sports' => collect((array) ($this->resource['sports'] ?? []))
                ->map(fn (mixed $category): array => (new CategoryResource($category))->resolve($request))
                ->values()->all(),
            'filter_tags' => array_values((array) ($this->resource['filter_tags'] ?? [])),
        ];
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => ['catalog_version' => (string) ($this->resource['catalog_version'] ?? '')],
            'request_id' => RequestId::from($request),
        ];
    }
}
