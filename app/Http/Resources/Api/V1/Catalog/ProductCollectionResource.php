<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Services\Catalog\CatalogReadService;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class ProductCollectionResource extends ApiResource
{
    /** @return array<int, array<string, mixed>> */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource['paginator'];

        return collect($paginator->items())
            ->map(fn (mixed $product): array => (new ProductCardResource($product))->resolve($request))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        $paginator = $this->resource['paginator'];

        return [
            'meta' => array_merge(CatalogReadService::paginationMeta($paginator), [
                'filters' => (array) ($this->resource['filters'] ?? []),
                'active_filter_count' => (int) ($this->resource['active_filter_count'] ?? 0),
                'filter_options' => (array) ($this->resource['filter_options'] ?? []),
                'sort_options' => array_values((array) ($this->resource['sort_options'] ?? [])),
                'catalog_version' => (string) ($this->resource['catalog_version'] ?? ''),
            ]),
            'request_id' => RequestId::from($request),
        ];
    }
}
