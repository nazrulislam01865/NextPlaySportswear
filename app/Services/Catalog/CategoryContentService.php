<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\CategoryContentBlock;
use App\Models\Product;
use App\Services\Storefront\ProductCatalogService;
use App\Support\PublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryContentService
{
    public function __construct(private readonly ProductCatalogService $products)
    {
    }

    /** @return Collection<int, array<string, mixed>> */
    public function resolve(Category $category): Collection
    {
        return $category->contentBlocks
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(fn (CategoryContentBlock $block): array => $this->resolveBlock($category, $block))
            ->values();
    }

    /** @return array<string, mixed> */
    private function resolveBlock(Category $category, CategoryContentBlock $block): array
    {
        $settings = is_array($block->settings) ? $block->settings : [];
        $limit = max(1, min(12, (int) ($settings['limit'] ?? 8)));
        $data = [
            'id' => $block->id,
            'type' => $block->block_type,
            'heading' => $block->heading,
            'subheading' => $block->subheading,
            'content_html' => $block->content_html,
            'image' => $block->publicImageUrl(),
            'image_alt' => $block->image_alt ?: $block->heading,
            'button_label' => $block->button_label,
            'button_url' => $block->button_url,
            'settings' => $settings,
            'items' => [],
        ];

        if (in_array($block->block_type, ['featured_products', 'selected_products'], true)) {
            $query = Product::query()->published()->with([
                'category', 'subcategory', 'categories', 'attributeValues.attribute', 'images',
                'optionGroups.values', 'sizeGroups.sizes', 'priceTiers', 'artworkMethods',
                'productionSpeeds', 'faqs',
            ]);

            if ($block->block_type === 'featured_products') {
                $query->whereHas('categories', fn (Builder $builder) => $builder
                    ->where('categories.id', $category->id)
                    ->where('category_product.is_featured', true));
            } else {
                $ids = collect($settings['product_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->take(50)->all();
                $query->whereIn('products.id', $ids);
            }

            $data['items'] = $query
                ->orderByDesc('products.is_featured')
                ->orderBy('products.sort_order')
                ->limit($limit)
                ->get()
                ->map(fn (Product $product) => $this->products->fromModel($product))
                ->all();
        }

        if ($block->block_type === 'child_categories') {
            $data['items'] = $category->children
                ->where('status', 'active')
                ->where('is_active', true)
                ->take($limit)
                ->map(fn (Category $child) => [
                    'title' => $child->name,
                    'description' => $child->short_description ?: $child->description,
                    'image' => $child->thumbnailUrl(),
                    'alt' => $child->thumbnail_alt ?: $child->name,
                    'url' => route('categories.show', $child->slug),
                    'count' => $child->products_count ?? 0,
                ])->values()->all();
        }

        if ($block->block_type === 'related_categories') {
            $data['items'] = Category::query()
                ->storefrontReachable()
                ->where('id', '!=', $category->id)
                ->where('parent_id', $category->parent_id)
                ->ordered()
                ->limit($limit)
                ->get()
                ->map(fn (Category $item) => [
                    'title' => $item->name,
                    'description' => $item->short_description ?: $item->description,
                    'image' => $item->thumbnailUrl(),
                    'alt' => $item->thumbnail_alt ?: $item->name,
                    'url' => route('categories.show', $item->slug),
                ])->all();
        }

        if ($block->block_type === 'highlights') {
            $data['items'] = collect($settings['items'] ?? $category->highlights ?? [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => ['label' => trim($item)])
                ->take(20)->values()->all();
        }

        if ($block->block_type === 'faq') {
            $data['items'] = $category->faqs
                ->where('is_active', true)
                ->take($limit)
                ->map(fn ($faq) => ['question' => $faq->question, 'answer_html' => $faq->answer_html])
                ->values()->all();
        }

        if (in_array($block->block_type, ['logo_list', 'trust_badges'], true)) {
            $data['items'] = collect($settings['items'] ?? [])
                ->filter(fn ($item) => is_array($item) && filled($item['label'] ?? null))
                ->map(fn ($item) => [
                    'label' => (string) $item['label'],
                    'image' => filter_var($item['image'] ?? null, FILTER_VALIDATE_URL) ?: null,
                    'url' => $this->safePublicUrl($item['url'] ?? null),
                    'description' => (string) ($item['description'] ?? ''),
                ])->take(30)->values()->all();
        }

        if ($block->block_type === 'video') {
            $data['video_embed_url'] = $this->videoEmbedUrl($settings['video_url'] ?? null);
        }

        return $data;
    }

    private function videoEmbedUrl(mixed $url): ?string
    {
        $url = trim((string) $url);
        if (preg_match('~^(?:https?://)?(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]{6,20})~', $url, $matches)
            || preg_match('~^(?:https?://)?youtu\.be/([A-Za-z0-9_-]{6,20})~', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
        }
        if (preg_match('~^(?:https?://)?(?:www\.)?vimeo\.com/(\d{6,12})~', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }
        return null;
    }

    private function safePublicUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        return PublicUrl::isAllowed($url) ? $url : null;
    }
}
