<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $product = (array) $this->resource;
        $stockQuantity = isset($product['stock_quantity']) ? (int) $product['stock_quantity'] : null;
        $tracksInventory = (bool) ($product['track_inventory'] ?? false);
        $allowBackorder = (bool) ($product['allow_backorder'] ?? false);

        return [
            'id' => isset($product['id']) ? (int) $product['id'] : null,
            'slug' => (string) ($product['slug'] ?? ''),
            'title' => (string) ($product['title'] ?? ''),
            'short_title' => (string) ($product['short_title'] ?? $product['title'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'summary' => (string) ($product['summary'] ?? ''),
            'brand' => (string) ($product['brand'] ?? ''),
            'product_type' => (string) ($product['product_type'] ?? ''),
            'category' => [
                'name' => (string) ($product['category'] ?? ''),
                'slug' => $product['category_slug'] ?? null,
                'subcategory_name' => $product['subcategory'] ?? null,
                'subcategory_slug' => $product['subcategory_slug'] ?? null,
            ],
            'categories' => array_values((array) ($product['categories'] ?? [])),
            'pricing' => [
                'currency' => (string) ($product['currency'] ?? 'USD'),
                'display' => (string) ($product['price'] ?? ''),
                'base_amount' => isset($product['base_price']) ? (float) $product['base_price'] : null,
                'unit_amount' => isset($product['display_unit_price']) ? (float) $product['display_unit_price'] : null,
                'discount_amount' => isset($product['discount_price']) ? (float) $product['discount_price'] : null,
                'original_amount' => isset($product['original_price']) ? (float) $product['original_price'] : null,
                'original_label' => $product['original_price_label'] ?? null,
                'compare_at_amount' => isset($product['display_compare_at_price']) ? (float) $product['display_compare_at_price'] : null,
                'compare_at_label' => $product['compare_at_price_label'] ?? null,
                'discount_percentage' => isset($product['discount_percentage']) ? (int) $product['discount_percentage'] : null,
                'has_bulk_pricing' => (bool) ($product['has_bulk_pricing'] ?? false),
            ],
            'quantity' => [
                'minimum' => isset($product['minimum_quantity']) ? (int) $product['minimum_quantity'] : 1,
                'maximum' => isset($product['maximum_quantity']) && $product['maximum_quantity'] !== null
                    ? (int) $product['maximum_quantity']
                    : null,
            ],
            'inventory' => [
                'tracked' => $tracksInventory,
                'stock_quantity' => $stockQuantity,
                'allow_backorder' => $allowBackorder,
                'status' => $tracksInventory && ($stockQuantity ?? 0) <= 0 && ! $allowBackorder
                    ? 'out_of_stock'
                    : ($tracksInventory ? 'in_stock' : 'made_to_order'),
            ],
            'media' => [
                'image' => $product['image'] ?? null,
                'alt' => (string) ($product['alt'] ?? $product['title'] ?? ''),
                'gallery' => array_values((array) ($product['gallery'] ?? [])),
            ],
            'rating' => [
                'average' => isset($product['rating']) && is_numeric($product['rating']) ? (float) $product['rating'] : null,
                'count' => isset($product['reviews_count']) && is_numeric($product['reviews_count']) ? (int) $product['reviews_count'] : 0,
                'has_reviews' => (bool) ($product['has_reviews'] ?? false),
            ],
            'flags' => [
                'featured' => (bool) ($product['is_featured'] ?? false),
                'customizable' => (bool) ($product['is_customizable'] ?? false),
            ],
            'badge' => [
                'label' => $product['tag'] ?? null,
                'color' => $product['tag_color'] ?? null,
            ],
            'customization_options' => array_values((array) ($product['customization_options'] ?? [])),
            'features' => array_values((array) ($product['features'] ?? [])),
            'tags' => array_values((array) ($product['tags'] ?? [])),
            'url' => $product['url'] ?? null,
        ];
    }
}
