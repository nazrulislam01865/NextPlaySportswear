<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class ProductResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $product = (array) ($this->resource['product'] ?? []);
        $card = (new ProductCardResource($product))->resolve($request);

        $card['description'] = (string) ($product['description'] ?? '');
        $card['description_html'] = $product['description_html'] ?? null;
        $card['detail_information_html'] = $product['detail_information_html'] ?? null;
        $card['customization_artwork_html'] = $product['customization_artwork_html'] ?? null;
        $card['attributes'] = (array) ($product['attributes'] ?? []);
        $card['specifications'] = (array) ($product['detail_information'] ?? $product['details'] ?? []);
        $card['summary_specifications'] = (array) ($product['summary_detail_information'] ?? []);
        $card['reviews'] = [
            'average' => isset($product['rating']) && is_numeric($product['rating']) ? (float) $product['rating'] : null,
            'count' => isset($product['reviews_count']) && is_numeric($product['reviews_count']) ? (int) $product['reviews_count'] : 0,
            'items' => array_values((array) ($product['review_items'] ?? [])),
            'distribution' => array_values((array) ($product['review_distribution'] ?? [])),
            'favorites_count' => isset($product['favorites_count']) ? (int) $product['favorites_count'] : null,
        ];
        $card['pricing'] = array_merge((array) $card['pricing'], [
            'tiers' => array_values((array) ($product['price_tiers'] ?? [])),
            'table' => (array) ($product['price_table'] ?? []),
            'fabric_tables' => array_values((array) ($product['fabric_price_tables'] ?? [])),
        ]);
        $card['configuration'] = [
            'product_profile' => (string) ($product['product_profile'] ?? 'standard'),
            'option_groups' => array_values((array) ($product['option_groups'] ?? [])),
            'size_groups' => array_values((array) ($product['size_groups'] ?? [])),
            'artwork_upload' => (array) ($product['artwork_upload'] ?? []),
            'artwork_methods' => array_values((array) ($product['artwork_methods'] ?? [])),
            'roster' => (array) ($product['roster'] ?? $product['jersey_roster'] ?? []),
            'sample' => (array) ($product['sample'] ?? []),
        ];
        $card['fulfillment'] = [
            'content_html' => $product['fulfillment_html'] ?? null,
            'production_methods_enabled' => (bool) ($product['production_methods_enabled'] ?? false),
            'production_speeds' => array_values((array) ($product['production_speeds'] ?? [])),
            'shipping_methods_enabled' => (bool) ($product['shipping_methods_enabled'] ?? false),
            'shipping_methods' => array_values((array) ($product['shipping_methods'] ?? [])),
        ];
        $card['faqs'] = array_values((array) ($product['faqs'] ?? []));
        $card['seo'] = [
            'meta_title' => $product['meta_title'] ?? null,
            'meta_description' => $product['meta_description'] ?? null,
            'meta_keywords' => $product['meta_keywords'] ?? null,
            'canonical' => $product['canonical_url'] ?? $product['url'] ?? null,
            'robots' => $product['robots'] ?? 'index, follow',
            'og_title' => $product['og_title'] ?? null,
            'og_description' => $product['og_description'] ?? null,
            'og_image' => $product['og_image'] ?? $product['image'] ?? null,
            'schema' => is_array($product['custom_schema'] ?? null) ? $product['custom_schema'] : null,
        ];
        $card['related_products'] = collect((array) ($this->resource['related_products'] ?? []))
            ->map(fn (mixed $related): array => (new ProductCardResource($related))->resolve($request))
            ->values()
            ->all();

        return $card;
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'catalog_version' => (string) ($this->resource['catalog_version'] ?? ''),
            ],
            'request_id' => RequestId::from($request),
        ];
    }
}
