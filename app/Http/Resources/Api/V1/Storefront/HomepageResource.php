<?php

namespace App\Http\Resources\Api\V1\Storefront;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

final class HomepageResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'seo' => (array) ($this->resource['seo'] ?? []),
            'slides' => array_values((array) ($this->resource['slides'] ?? [])),
            'sections' => array_values((array) ($this->resource['homeSections'] ?? [])),
            'categories' => array_values((array) ($this->resource['categories'] ?? [])),
            'featured_products' => array_values((array) ($this->resource['featuredProducts'] ?? [])),
            'latest_products' => array_values((array) ($this->resource['latestProducts'] ?? [])),
            'latest_products_signature' => (string) ($this->resource['latestProductsSignature'] ?? ''),
            'best_selling_products' => array_values((array) ($this->resource['bestSellingProducts'] ?? [])),
            'sports' => array_values((array) ($this->resource['sports'] ?? [])),
        ];
    }
}
