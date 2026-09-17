<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Services\Catalog\CatalogReadService;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class CategoryDetailResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource['products'];

        return [
            'category' => (new CategoryResource($this->resource['category'] ?? []))->resolve($request),
            'breadcrumbs' => array_values((array) ($this->resource['breadcrumbs'] ?? [])),
            'related_categories' => collect((array) ($this->resource['related_categories'] ?? []))
                ->map(fn (mixed $category): array => (new CategoryResource($category))->resolve($request))
                ->values()->all(),
            'products' => [
                'data' => collect($paginator->items())
                    ->map(fn (mixed $product): array => (new ProductCardResource($product))->resolve($request))
                    ->values()->all(),
                'meta' => CatalogReadService::paginationMeta($paginator),
            ],
            'filters' => (array) ($this->resource['filters'] ?? []),
            'active_filter_count' => (int) ($this->resource['active_filter_count'] ?? 0),
            'filter_options' => (array) ($this->resource['filter_options'] ?? []),
            'sort_options' => array_values((array) ($this->resource['sort_options'] ?? [])),
            'content_blocks' => array_values((array) ($this->resource['content_blocks'] ?? [])),
            'faqs' => array_values((array) ($this->resource['faqs'] ?? [])),
            'seo' => (array) ($this->resource['seo'] ?? []),
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
