<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $category = (array) $this->resource;

        return [
            'id' => isset($category['id']) ? (int) $category['id'] : null,
            'slug' => (string) ($category['slug'] ?? ''),
            'type' => (string) ($category['group'] ?? ''),
            'title' => (string) ($category['title'] ?? ''),
            'short_title' => (string) ($category['short_title'] ?? $category['title'] ?? ''),
            'eyebrow' => $category['eyebrow'] ?? null,
            'description' => $category['description'] ?? null,
            'description_html' => $category['description_html'] ?? null,
            'best_for' => $category['best_for'] ?? null,
            'parent' => [
                'name' => $category['parent_name'] ?? null,
                'slug' => $category['parent_slug'] ?? null,
            ],
            'depth' => (int) ($category['depth'] ?? 0),
            'is_subcategory' => (bool) ($category['is_subcategory'] ?? false),
            'tags' => array_values((array) ($category['tags'] ?? [])),
            'media' => [
                'image' => $category['image'] ?? null,
                'icon' => $category['icon'] ?? null,
                'banner' => $category['banner'] ?? null,
                'mobile_banner' => $category['mobile_banner'] ?? null,
                'alt' => $category['alt'] ?? null,
                'banner_alt' => $category['banner_alt'] ?? null,
            ],
            'highlights' => array_values((array) ($category['highlights'] ?? [])),
            'product_count' => (int) ($category['product_count'] ?? 0),
            'is_featured' => (bool) ($category['is_featured'] ?? false),
            'cta' => [
                'label' => $category['link_label'] ?? null,
                'url' => $category['url'] ?? null,
            ],
            'seo' => [
                'meta_title' => $category['meta_title'] ?? null,
                'meta_description' => $category['meta_description'] ?? null,
            ],
        ];
    }
}
