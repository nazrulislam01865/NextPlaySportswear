<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CategoryTreeService
{
    private const TREE_CACHE_KEY = 'catalog.category-tree.v3';

    /** @return Collection<int, Category> */
    public function tree(bool $menuOnly = false): Collection
    {
        $key = self::TREE_CACHE_KEY.($menuOnly ? '.menu' : '.catalog');
        $ttl = max(1, (int) config('catalog.category_cache_seconds', 300));

        $payload = Cache::remember(
            $key,
            $ttl,
            fn (): array => $this->buildTreePayload($menuOnly)
        );

        if (! is_array($payload)) {
            Cache::forget($key);
            $payload = $this->buildTreePayload($menuOnly);
            Cache::put($key, $payload, $ttl);
        }

        return collect($payload)
            ->filter(fn (mixed $node): bool => is_array($node))
            ->map(fn (array $node): Category => $this->hydrateTreeNode($node))
            ->values();
    }

    /**
     * Store only scalar arrays in persistent cache. Caching Eloquent models or
     * Collections is incompatible with cache.serializable_classes=false and
     * can produce __PHP_Incomplete_Class values on subsequent requests.
     *
     * @return array<int, array{attributes: array<string, mixed>, children: array<int, mixed>}>
     */
    private function buildTreePayload(bool $menuOnly): array
    {
        $categories = Category::query()
            ->storefrontReachable($menuOnly)
            ->withCount([
                'products as products_count' => fn ($query) => $query->published(),
            ])
            ->ordered()
            ->get();

        $byParent = $categories->groupBy(fn (Category $category): int => (int) ($category->parent_id ?? 0));
        $visited = [];

        $build = function (Category $category, int $depth = 0) use (&$build, &$visited, $byParent): array {
            $id = (int) $category->id;

            if ($depth > 50 || isset($visited[$id])) {
                return [
                    'attributes' => $category->getAttributes(),
                    'children' => [],
                ];
            }

            $visited[$id] = true;
            $children = ($byParent->get($id) ?? collect())
                ->map(fn (Category $child): array => $build($child, $depth + 1))
                ->values()
                ->all();
            unset($visited[$id]);

            return [
                'attributes' => $category->getAttributes(),
                'children' => $children,
            ];
        };

        return ($byParent->get(0) ?? collect())
            ->map(fn (Category $category): array => $build($category))
            ->values()
            ->all();
    }

    /**
     * @param  array{attributes?: mixed, children?: mixed}  $node
     */
    private function hydrateTreeNode(array $node): Category
    {
        $attributes = is_array($node['attributes'] ?? null) ? $node['attributes'] : [];
        $category = new Category();
        $category->setRawAttributes($attributes, true);
        $category->exists = isset($attributes['id']);

        $childrenPayload = is_array($node['children'] ?? null) ? $node['children'] : [];
        $children = collect($childrenPayload)
            ->filter(fn (mixed $child): bool => is_array($child))
            ->map(fn (array $child): Category => $this->hydrateTreeNode($child))
            ->values();

        $category->setRelation('childrenRecursive', $children);
        $category->setRelation('children', $children);

        return $category;
    }

    /** @return Collection<int, Category> */
    public function flatOptions(?int $excludeCategoryId = null): Collection
    {
        $excluded = [];
        if ($excludeCategoryId) {
            $excluded = $this->descendantIds($excludeCategoryId, true);
        }

        return Category::query()
            ->when($excluded !== [], fn ($query) => $query->whereNotIn('id', $excluded))
            ->orderBy('tree_path')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category): Category {
                $category->setAttribute('indented_name', str_repeat('— ', $category->depth).$category->name);

                return $category;
            });
    }

    /** @return array<int> */
    public function descendantIds(int $categoryId, bool $includeSelf = false): array
    {
        $query = DB::table('category_closure')
            ->where('ancestor_id', $categoryId)
            ->orderBy('depth');

        if (! $includeSelf) {
            $query->where('depth', '>', 0);
        }

        return $query->pluck('descendant_id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return Collection<int, Category> */
    public function breadcrumbs(Category $category): Collection
    {
        $ancestorIds = DB::table('category_closure')
            ->where('descendant_id', $category->id)
            ->where('depth', '>', 0)
            ->orderByDesc('depth')
            ->pluck('ancestor_id');

        $ancestors = Category::query()->whereIn('id', $ancestorIds)->get()->keyBy('id');

        return $ancestorIds->map(fn ($id) => $ancestors->get($id))->filter()->push($category);
    }

    public function assertValidParent(Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category->exists && $category->id === $parentId) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
        }

        $parent = Category::query()->find($parentId);
        if (! $parent) {
            throw ValidationException::withMessages(['parent_id' => 'The selected parent category does not exist.']);
        }

        if ($category->exists && in_array($parentId, $this->descendantIds($category->id), true)) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be moved beneath one of its descendants.']);
        }

        $subtreeDepth = $category->exists
            ? (int) DB::table('category_closure')->where('ancestor_id', $category->id)->max('depth')
            : 0;
        $resultingDepth = $parent->depth + 1 + $subtreeDepth;

        if ($resultingDepth > config('catalog.max_category_depth')) {
            throw ValidationException::withMessages([
                'parent_id' => 'This move would exceed the maximum category depth of '.config('catalog.max_category_depth').'.',
            ]);
        }
    }

    public function rebuildClosure(): void
    {
        $categories = Category::query()->withTrashed()->select(['id', 'parent_id'])->get()->keyBy('id');
        $resolved = [];
        $visiting = [];
        $rows = [];
        $updates = [];

        $resolve = function (int $id) use (&$resolve, &$resolved, &$visiting, &$rows, &$updates, $categories): array {
            if (isset($resolved[$id])) {
                return $resolved[$id];
            }
            if (isset($visiting[$id])) {
                throw new RuntimeException('Circular category hierarchy detected.');
            }

            $category = $categories->get($id);
            if (! $category) {
                return [];
            }

            $visiting[$id] = true;
            $chain = [$id];
            if ($category->parent_id && $categories->has((int) $category->parent_id)) {
                $chain = array_merge($resolve((int) $category->parent_id), [$id]);
            }
            unset($visiting[$id]);

            $depth = count($chain) - 1;
            if ($depth > config('catalog.max_category_depth')) {
                throw new RuntimeException("Category {$id} exceeds the configured maximum depth.");
            }

            $updates[$id] = [
                'depth' => $depth,
                'tree_path' => '/'.implode('/', $chain).'/',
            ];

            foreach (array_reverse($chain) as $distance => $ancestorId) {
                $rows[] = [
                    'ancestor_id' => $ancestorId,
                    'descendant_id' => $id,
                    'depth' => $distance,
                ];
            }

            return $resolved[$id] = $chain;
        };

        foreach ($categories->keys() as $categoryId) {
            $resolve((int) $categoryId);
        }

        DB::transaction(function () use ($rows, $updates): void {
            DB::table('category_closure')->delete();
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('category_closure')->insert($chunk);
            }
            foreach ($updates as $id => $payload) {
                DB::table('categories')->where('id', $id)->update($payload);
            }
        });

        $this->flushCache();
    }

    public function flushCache(): void
    {
        Cache::forget(self::TREE_CACHE_KEY.'.menu');
        Cache::forget(self::TREE_CACHE_KEY.'.catalog');
        Cache::forget('catalog.category-tree.v2.menu');
        Cache::forget('catalog.category-tree.v2.catalog');
        Cache::forget('catalog.navigation.all');
        Cache::forget('catalog.featured-categories');
        $facetVersion = (int) Cache::get('catalog.category-facets.version', 1);
        Cache::forever('catalog.category-facets.version', $facetVersion + 1);
    }
}
