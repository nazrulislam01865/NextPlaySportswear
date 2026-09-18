<?php

namespace App\Http\Resources\Api\V1\Storefront;

use App\Http\Resources\Api\V1\ApiResource;
use App\ViewModels\Catalog\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
            'navigation' => $this->navigationItems($this->resource['navigation'] ?? [], $request),
            'menus' => $this->menus($this->resource['storefrontMenus'] ?? [], $request),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function menus(mixed $menus, Request $request): array
    {
        if (! is_array($menus)) return [];
        $serialized = [];
        foreach ($menus as $key => $items) {
            $serialized[(string) $key] = $this->navigationItems($items, $request);
        }
        return $serialized;
    }

    /** @return array<int, array<string, mixed>> */
    private function navigationItems(mixed $items, Request $request): array
    {
        $collection = $items instanceof Collection ? $items : collect(is_array($items) ? $items : []);
        return $collection
            ->filter(fn (mixed $item): bool => $item instanceof NavigationItem || is_array($item))
            ->map(fn (mixed $item): array => (new NavigationItemResource($item))->resolve($request))
            ->filter(fn (array $item): bool => $item !== [])
            ->values()->all();
    }
}
