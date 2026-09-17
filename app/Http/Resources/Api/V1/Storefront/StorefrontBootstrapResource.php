<?php

namespace App\Http\Resources\Api\V1\Storefront;

use App\Http\Resources\Api\V1\ApiResource;
use App\ViewModels\Catalog\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class StorefrontBootstrapResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'site' => (array) ($this->resource['site'] ?? []),
            'customer' => $this->resource['customer'] ?? null,
            'cart' => (array) ($this->resource['cart'] ?? []),
            'wishlist' => (array) ($this->resource['wishlist'] ?? []),
            'navigation' => $this->menus($this->resource['navigation'] ?? [], $request),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function menus(mixed $menus, Request $request): array
    {
        if (! is_array($menus)) {
            return [];
        }

        $serialized = [];

        foreach ($menus as $key => $items) {
            $serialized[(string) $key] = $this->navigationItems($items, $request);
        }

        return $serialized;
    }

    /** @return array<int, array<string, mixed>> */
    private function navigationItems(mixed $items, Request $request): array
    {
        $collection = $items instanceof Collection
            ? $items
            : collect(is_array($items) ? $items : []);

        return $collection
            ->filter(fn (mixed $item): bool => $item instanceof NavigationItem || is_array($item))
            ->map(fn (mixed $item): array => (new NavigationItemResource($item))->resolve($request))
            ->filter(fn (array $item): bool => $item !== [])
            ->values()
            ->all();
    }
}
