<?php

namespace App\ViewModels\Catalog;

use App\Support\PublicUrl;
use Illuminate\Support\Collection;

/**
 * Immutable storefront navigation item hydrated from an array-only cache payload.
 *
 * Keeping framework models out of persistent cache avoids unsafe object
 * unserialization and remains compatible with cache.serializable_classes=false.
 */
final class NavigationItem
{
    /**
     * @param  Collection<int, self>  $childrenRecursive
     */
    public function __construct(
        public readonly string $label,
        public readonly string $link_type,
        public readonly ?string $category,
        public readonly ?string $route_name,
        public readonly ?string $url,
        public readonly string $target,
        public readonly string $css_class,
        public readonly Collection $childrenRecursive,
    ) {
    }

    /**
     * @param  array{
     *     label?: mixed,
     *     link_type?: mixed,
     *     category_slug?: mixed,
     *     route_name?: mixed,
     *     url?: mixed,
     *     target?: mixed,
     *     css_class?: mixed,
     *     children?: mixed
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        $children = is_array($payload['children'] ?? null)
            ? collect($payload['children'])
                ->filter(fn (mixed $child): bool => is_array($child))
                ->map(fn (array $child): self => self::fromArray($child))
                ->values()
            : collect();

        $linkType = in_array($payload['link_type'] ?? null, ['category', 'route', 'custom'], true)
            ? (string) $payload['link_type']
            : 'custom';

        $target = ($payload['target'] ?? '_self') === '_blank' ? '_blank' : '_self';

        return new self(
            label: trim((string) ($payload['label'] ?? '')),
            link_type: $linkType,
            category: filled($payload['category_slug'] ?? null) ? (string) $payload['category_slug'] : null,
            route_name: filled($payload['route_name'] ?? null) ? (string) $payload['route_name'] : null,
            url: filled($payload['url'] ?? null) ? (string) $payload['url'] : null,
            target: $target,
            css_class: trim((string) ($payload['css_class'] ?? '')),
            childrenRecursive: $children,
        );
    }

    public function resolvedUrl(): string
    {
        if ($this->link_type === 'category' && filled($this->category)) {
            return route('categories.show', $this->category);
        }

        if ($this->link_type === 'route' && filled($this->route_name) && app('router')->has($this->route_name)) {
            return route($this->route_name);
        }

        return PublicUrl::isAllowed($this->url) ? (string) $this->url : '#';
    }
}
