<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\ViewModels\Catalog\NavigationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    private const CACHE_VERSION = 'v4';

    /** @var array<string, Collection<int, NavigationItem>> */
    private array $runtimeItems = [];

    /** @var Collection<int, string>|null */
    private ?Collection $runtimeReachableCategorySlugs = null;

    private const LOCATIONS = [
        'header-primary',
        'footer-shop',
        'footer-sports',
        'footer-support',
        'footer-company',
    ];

    /** @return Collection<int, NavigationItem> */
    public function items(string $location): Collection
    {
        if (isset($this->runtimeItems[$location])) {
            return $this->runtimeItems[$location];
        }

        $cacheKey = $this->cacheKey($location);
        $ttl = max(1, (int) config('catalog.navigation_cache_seconds', 300));

        $payload = Cache::remember(
            $cacheKey,
            $ttl,
            fn (): array => $this->buildPayload($location)
        );

        // Protect the storefront from stale/corrupt values created by older
        // releases that cached Eloquent collections directly.
        if (! is_array($payload)) {
            Cache::forget($cacheKey);
            $payload = $this->buildPayload($location);
            Cache::put($cacheKey, $payload, $ttl);
        }

        return $this->runtimeItems[$location] = collect($payload)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): NavigationItem => NavigationItem::fromArray($item))
            ->values();
    }

    /** @return array<string, Collection<int, NavigationItem>> */
    public function storefrontMenus(): array
    {
        return [
            'header' => $this->items('header-primary'),
            'footer_shop' => $this->items('footer-shop'),
            'footer_sports' => $this->items('footer-sports'),
            'footer_support' => $this->items('footer-support'),
            'footer_company' => $this->items('footer-company'),
        ];
    }

    public function flushCache(): void
    {
        $this->runtimeItems = [];

        foreach (self::LOCATIONS as $location) {
            Cache::forget($this->cacheKey($location));
            // Remove the legacy keys that stored serialized model objects.
            Cache::forget('catalog.navigation.'.$location);
        }

        Cache::forget('catalog.navigation.all');
    }

    /**
     * Build a scalar-only payload using a fixed number of queries. The payload
     * is safe for database, file, Redis, or Memcached cache stores even when
     * object unserialization is disabled.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPayload(string $location): array
    {
        $menuId = Menu::query()
            ->where('location', $location)
            ->where('is_active', true)
            ->value('id');

        if (! $menuId) {
            return $location === 'header-primary' ? $this->buildDefaultHeaderPayload() : [];
        }

        $reachableCategories = $this->reachableCategorySlugs();

        /** @var Collection<int, MenuItem> $items */
        $items = MenuItem::query()
            ->where('menu_id', $menuId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id', 'parent_id', 'label', 'link_type', 'category_id',
                'route_name', 'url', 'target', 'css_class', 'sort_order',
            ]);

        $byParent = $items->groupBy(fn (MenuItem $item): int => (int) ($item->parent_id ?? 0));
        $visited = [];

        $build = function (int $parentId, int $depth = 0) use (&$build, &$visited, $byParent, $reachableCategories): array {
            // A hard guard protects against malformed legacy data even though
            // the admin request validation already rejects cycles.
            if ($depth > 20) {
                return [];
            }

            return ($byParent->get($parentId) ?? collect())
                ->filter(function (MenuItem $item) use ($reachableCategories): bool {
                    if ($item->link_type !== 'category') {
                        return true;
                    }

                    return $item->category_id !== null
                        && $reachableCategories->has((int) $item->category_id);
                })
                ->map(function (MenuItem $item) use (&$build, &$visited, $reachableCategories, $depth): ?array {
                    $id = (int) $item->id;
                    if (isset($visited[$id])) {
                        return null;
                    }

                    $visited[$id] = true;
                    $children = $build($id, $depth + 1);
                    unset($visited[$id]);

                    return [
                        'label' => (string) $item->label,
                        'link_type' => (string) $item->link_type,
                        'category_slug' => $item->category_id !== null
                            ? $reachableCategories->get((int) $item->category_id)
                            : null,
                        'route_name' => $item->route_name,
                        'url' => $item->url,
                        'target' => $item->target === '_blank' ? '_blank' : '_self',
                        'css_class' => (string) ($item->css_class ?? ''),
                        'children' => $children,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        };

        $payload = $build(0);

        return $location === 'header-primary'
            ? $this->normalizeHeaderPayload($payload)
            : $payload;
    }

    /**
     * The header menu must stay database-driven, but the Shop Products mega
     * menu should always reflect the current category tree. This avoids stale
     * seeded menu rows after imports and prevents duplicate Home/Shop entries.
     *
     * @param  array<int, array<string, mixed>>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHeaderPayload(array $payload): array
    {
        $items = collect($payload)
            ->filter(fn (mixed $item): bool => is_array($item) && trim((string) ($item['label'] ?? '')) !== '')
            ->values();

        $categoryTree = $this->buildDynamicCategoryNavigation();
        $shopItem = [
            'label' => 'Shop Products',
            'link_type' => 'route',
            'category_slug' => null,
            'route_name' => 'categories.index',
            'url' => null,
            'target' => '_self',
            'css_class' => '',
            'children' => $categoryTree,
        ];

        $hasHome = false;
        $shopInserted = false;
        $normalized = collect();

        foreach ($items as $item) {
            if ($this->isHomeNavigationItem($item)) {
                if ($hasHome) {
                    continue;
                }

                $hasHome = true;
                $normalized->push($item);
                continue;
            }

            if ($this->isShopNavigationItem($item)) {
                if ($shopInserted) {
                    continue;
                }

                $shopInserted = true;
                $normalized->push(array_replace($item, $shopItem));
                continue;
            }

            $normalized->push($item);
        }

        if (! $hasHome) {
            $hasHome = true;
            $normalized->prepend([
                'label' => 'Home',
                'link_type' => 'route',
                'category_slug' => null,
                'route_name' => 'home',
                'url' => null,
                'target' => '_self',
                'css_class' => '',
                'children' => [],
            ]);
        }

        if (! $shopInserted) {
            $insertAt = $hasHome ? 1 : 0;
            $normalized = $normalized->values();
            $normalized->splice($insertAt, 0, [$shopItem]);
        }

        return $normalized->values()->all();
    }

    /** @return Collection<int, string> */
    private function reachableCategorySlugs(): Collection
    {
        if ($this->runtimeReachableCategorySlugs !== null) {
            return $this->runtimeReachableCategorySlugs;
        }

        $cacheVersion = (int) Cache::get('catalog.category-facets.version', 1);
        $cacheKey = 'catalog.navigation.reachable-categories.'.self::CACHE_VERSION.'.'.$cacheVersion;
        $ttl = max(60, (int) config('catalog.navigation_cache_seconds', 3600));

        $rows = Cache::remember($cacheKey, $ttl, fn (): array => Category::query()
            ->storefrontReachable(menuOnly: true)
            ->pluck('slug', 'id')
            ->mapWithKeys(fn (mixed $slug, mixed $id): array => [(int) $id => (string) $slug])
            ->all());

        return $this->runtimeReachableCategorySlugs = collect($rows)
            ->mapWithKeys(fn (mixed $slug, mixed $id): array => [(int) $id => (string) $slug]);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildDefaultHeaderPayload(): array
    {
        return $this->normalizeHeaderPayload([
            [
                'label' => 'Home',
                'link_type' => 'route',
                'category_slug' => null,
                'route_name' => 'home',
                'url' => null,
                'target' => '_self',
                'css_class' => '',
                'children' => [],
            ],
            [
                'label' => 'Shop Products',
                'link_type' => 'route',
                'category_slug' => null,
                'route_name' => 'categories.index',
                'url' => null,
                'target' => '_self',
                'css_class' => '',
                'children' => [],
            ],
            [
                'label' => 'All Products',
                'link_type' => 'route',
                'category_slug' => null,
                'route_name' => 'products.index',
                'url' => null,
                'target' => '_self',
                'css_class' => '',
                'children' => [],
            ],
            [
                'label' => 'Bulk Quote',
                'link_type' => 'route',
                'category_slug' => null,
                'route_name' => 'quote.request',
                'url' => null,
                'target' => '_self',
                'css_class' => 'text-brand-red',
                'children' => [],
            ],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildDynamicCategoryNavigation(): array
    {
        /** @var Collection<int, Category> $categories */
        $categories = Category::query()
            ->storefrontReachable(menuOnly: true)
            ->ordered()
            ->get(['id', 'parent_id', 'name', 'menu_label', 'slug']);

        $byParent = $categories->groupBy(fn (Category $category): int => (int) ($category->parent_id ?? 0));
        $visited = [];

        $build = function (int $parentId, int $depth = 0) use (&$build, &$visited, $byParent): array {
            if ($depth > 20) {
                return [];
            }

            return ($byParent->get($parentId) ?? collect())
                ->map(function (Category $category) use (&$build, &$visited, $depth): ?array {
                    $id = (int) $category->id;
                    if (isset($visited[$id])) {
                        return null;
                    }

                    $visited[$id] = true;
                    $children = $build($id, $depth + 1);
                    unset($visited[$id]);

                    return [
                        'label' => $category->displayLabel(),
                        'link_type' => 'category',
                        'category_slug' => $category->slug,
                        'route_name' => null,
                        'url' => null,
                        'target' => '_self',
                        'css_class' => '',
                        'children' => $children,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        };

        return $build(0);
    }

    /** @param array<string, mixed> $item */
    private function isHomeNavigationItem(array $item): bool
    {
        $label = str($item['label'] ?? '')->lower()->squish()->toString();

        return (($item['link_type'] ?? null) === 'route' && ($item['route_name'] ?? null) === 'home')
            || $label === 'home';
    }

    /** @param array<string, mixed> $item */
    private function isShopNavigationItem(array $item): bool
    {
        $label = str($item['label'] ?? '')->lower()->replace(['-', '_'], ' ')->squish()->toString();

        return (($item['link_type'] ?? null) === 'route' && ($item['route_name'] ?? null) === 'categories.index')
            || in_array($label, ['shop products', 'shop categories', 'categories'], true);
    }

    private function cacheKey(string $location): string
    {
        return 'catalog.navigation.'.self::CACHE_VERSION.'.'.$location;
    }
}
