<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $processIds = [
        'choose-product',
        'share-details',
        'review-mockup',
        'confirm-order',
        'production-shipping',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $legacy = DB::table('homepage_sections')
            ->whereIn('key', ['categories', 'process', 'latest_products', 'slider'])
            ->get()
            ->keyBy('key');

        foreach ($this->newRows() as $row) {
            $key = $row['key'];
            $existing = DB::table('homepage_sections')->where('key', $key)->first();
            $created = $existing === null;

            if ($created) {
                DB::table('homepage_sections')->insert($row);
                $existing = DB::table('homepage_sections')->where('key', $key)->first();
            }

            if (! $existing) {
                continue;
            }

            $updates = [];

            if ($key === 'new_arrivals' && isset($legacy['latest_products'])) {
                $source = $legacy['latest_products'];
                if ($created || $this->blank($existing->title ?? null)) {
                    $updates['title'] = $source->title ?: $row['title'];
                }
                if ($created) {
                    $updates['is_active'] = (bool) $source->is_active;
                }
            }

            if ($key === 'shop_by_category' && isset($legacy['categories'])) {
                $source = $legacy['categories'];
                if ($created || $this->blank($existing->title ?? null)) {
                    $updates['title'] = $source->title ?: $row['title'];
                }
                if ($created || $this->emptyJson($existing->items ?? null)) {
                    $updates['items'] = json_encode($this->withStableItemIds($this->decodeItems($source->items ?? null), 'category'), JSON_UNESCAPED_SLASHES);
                }
            }

            if ($key === 'shop_by_category' && ! ($created || $this->emptyJson($existing->items ?? null))) {
                $existingItems = $this->decodeItems($existing->items ?? null);
                $normalizedItems = $this->withStableItemIds($existingItems, 'category');
                if ($normalizedItems !== $existingItems) {
                    $updates['items'] = json_encode($normalizedItems, JSON_UNESCAPED_SLASHES);
                }
            }

            if ($key === 'shop_by_sport') {
                $existingItems = $this->decodeItems($existing->items ?? null);
                $normalizedItems = $this->withStableItemIds($existingItems, 'sport');
                if ($normalizedItems !== $existingItems) {
                    $updates['items'] = json_encode($normalizedItems, JSON_UNESCAPED_SLASHES);
                }
            }

            if ($key === 'design_process' && isset($legacy['process'])) {
                $source = $legacy['process'];
                if ($created || $this->blank($existing->title ?? null)) {
                    $updates['title'] = $source->title ?: $row['title'];
                }
                if ($created || $this->emptyJson($existing->items ?? null)) {
                    $updates['items'] = json_encode($this->withStableProcessIds($this->decodeItems($source->items ?? null)), JSON_UNESCAPED_SLASHES);
                }
            }

            if ($key === 'hero' && isset($legacy['slider']) && $created) {
                $updates['is_active'] = (bool) $legacy['slider']->is_active;
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('homepage_sections')->where('key', $key)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Legacy and new rows are preserved for rollback safety.
    }

    /** @return array<int, array<string, mixed>> */
    private function newRows(): array
    {
        $now = now();
        $base = static fn (string $key, string $name, int $sortOrder): array => [
            'key' => $key,
            'name' => $name,
            'eyebrow' => null,
            'title' => null,
            'description' => null,
            'primary_label' => null,
            'primary_url' => null,
            'secondary_label' => null,
            'secondary_url' => null,
            'image_path' => null,
            'image_url' => null,
            'image_alt' => null,
            'mobile_image_path' => null,
            'mobile_image_url' => null,
            'mobile_image_alt' => null,
            'hero_slides' => null,
            'items' => null,
            'settings' => null,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $rows = [];
        $rows[] = array_replace($base('hero', 'Hero Banner', 10), []);
        $rows[] = array_replace($base('audience', 'Audience Tiles', 20), [
            'items' => json_encode([
                ['id' => 'men', 'title' => 'MEN', 'url' => '/men', 'image_alt' => 'Men sportswear'],
                ['id' => 'women', 'title' => 'WOMEN', 'url' => '/products?q=women', 'image_alt' => 'Women sportswear'],
                ['id' => 'kids', 'title' => 'KIDS', 'url' => '/products?q=kids', 'image_alt' => 'Kids sportswear'],
            ], JSON_UNESCAPED_SLASHES),
        ]);
        $rows[] = array_replace($base('shop_by_sport', 'Shop By Sport', 30), [
            'title' => 'SHOP BY SPORT',
            'settings' => json_encode([
                'default_sport_id' => null,
                'quick_links' => [
                    ['id' => 'jersey', 'label' => 'JERSEY', 'url' => '/products?q=jersey'],
                    ['id' => 'bottoms', 'label' => 'BOTTOMS', 'url' => '/products?q=bottoms'],
                    ['id' => 'uniform-kits', 'label' => 'UNIFORM KITS', 'url' => '/products?q=uniform'],
                    ['id' => 'accessories', 'label' => 'ACCESSORIES', 'url' => '/products?q=accessories'],
                ],
            ], JSON_UNESCAPED_SLASHES),
        ]);
        $rows[] = array_replace($base('new_arrivals', 'New Arrivals', 40), ['title' => 'NEW ARRIVALS']);
        $rows[] = array_replace($base('shop_by_category', 'Shop By Category', 50), ['title' => 'SHOP BY CATEGORY', 'items' => json_encode([])]);
        $rows[] = array_replace($base('best_choices', 'Best Choices For You', 60), [
            'title' => 'BEST CHOICES FOR YOU',
            'settings' => json_encode(['tabs' => [
                'featured' => ['label' => 'FEATURED', 'enabled' => true],
                'popular' => ['label' => 'POPULAR', 'enabled' => true],
                'trending' => ['label' => 'TRENDING', 'enabled' => true],
            ]], JSON_UNESCAPED_SLASHES),
        ]);
        $rows[] = array_replace($base('season_sale', 'Season Sale', 70), [
            'title' => 'SEASON SALE', 'description' => 'UP TO 20% OFF', 'primary_label' => 'SHOP SALE',
            'primary_url' => '/products', 'image_alt' => 'NextPlay season sale',
        ]);
        $rows[] = array_replace($base('make_it_yours', 'Make It Yours', 80), [
            'title' => 'MAKE IT YOURS', 'primary_label' => 'Explore All', 'primary_url' => '/products',
        ]);
        $rows[] = array_replace($base('design_process', 'Design Process', 90), [
            'title' => 'HOW TO DESIGN A T-SHIRT USING NEXTPLAY',
            'items' => json_encode([
                ['id' => 'choose-product', 'title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
                ['id' => 'share-details', 'title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
                ['id' => 'review-mockup', 'title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
                ['id' => 'confirm-order', 'title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
                ['id' => 'production-shipping', 'title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
            ], JSON_UNESCAPED_SLASHES),
        ]);

        return $rows;
    }

    private function blank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function emptyJson(mixed $value): bool
    {
        return $this->decodeItems($value) === [];
    }

    /** @return array<int, array<string, mixed>> */
    private function decodeItems(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_array'));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }


    /** @param array<int, array<string, mixed>> $items @return array<int, array<string, mixed>> */
    private function withStableItemIds(array $items, string $prefix): array
    {
        $seen = [];

        return collect($items)->map(function (array $item, int $index) use ($prefix, &$seen): array {
            $candidate = Str::slug(trim((string) ($item['id'] ?? '')));
            if ($candidate === '') {
                $categoryId = (int) ($item['category_id'] ?? 0);
                $candidate = $categoryId > 0 ? $prefix.'-'.$categoryId : $prefix.'-'.($index + 1);
            }

            $base = $candidate;
            $suffix = 2;
            while (isset($seen[$candidate])) {
                $candidate = $base.'-'.$suffix;
                $suffix++;
            }

            $seen[$candidate] = true;
            $item['id'] = $candidate;

            return $item;
        })->values()->all();
    }

    /** @param array<int, array<string, mixed>> $items @return array<int, array<string, mixed>> */
    private function withStableProcessIds(array $items): array
    {
        return collect($items)->map(function (array $item, int $index): array {
            $item['id'] = trim((string) ($item['id'] ?? '')) ?: ($this->processIds[$index] ?? 'process-step-'.($index + 1));

            return $item;
        })->values()->all();
    }
};
