<?php

namespace App\Http\Resources\Api\V1\Storefront;

use App\Http\Resources\Api\V1\ApiResource;
use App\ViewModels\Catalog\NavigationItem;
use Illuminate\Http\Request;

final class NavigationItemResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $item = $this->navigationItem();

        if (! $item instanceof NavigationItem) {
            return [];
        }

        return [
            'label' => $item->label,
            'type' => $item->link_type,
            'category_slug' => $item->category,
            'icon_url' => $item->icon_url,
            'url' => $item->resolvedUrl(),
            'target' => $item->target,
            'css_class' => $item->css_class,
            'children' => $item->childrenRecursive
                ->map(fn (NavigationItem $child): array => (new self($child))->resolve($request))
                ->values()
                ->all(),
        ];
    }

    private function navigationItem(): ?NavigationItem
    {
        if ($this->resource instanceof NavigationItem) {
            return $this->resource;
        }

        return is_array($this->resource)
            ? NavigationItem::fromArray($this->resource)
            : null;
    }
}
