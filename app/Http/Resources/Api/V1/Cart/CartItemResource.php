<?php

namespace App\Http\Resources\Api\V1\Cart;

use App\Http\Resources\Api\V1\ApiResource;
use App\Services\Cart\CartArtworkService;
use Illuminate\Http\Request;

final class CartItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $item = (array) $this->resource;
        $product = (array) ($item['product'] ?? []);
        $customization = (array) ($item['customization'] ?? []);
        $artworkFiles = collect((array) ($customization['artwork_files'] ?? []))
            ->filter(fn ($file): bool => is_array($file) && filled($file['path'] ?? null))
            ->values();

        unset($customization['artwork_path'], $customization['artwork_original_name']);
        $customization['artwork_files'] = $artworkFiles->map(function (array $file, int $index) use ($item): array {
            $path = (string) $file['path'];

            return [
                'index' => $index,
                'name' => (string) ($file['original_name'] ?? 'Artwork file'),
                'size' => max(0, (int) ($file['size'] ?? 0)),
                'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
                'retention_token' => CartArtworkService::retentionToken($path),
                'view_url' => route('cart.items.artwork.show', [
                    'cartItem' => (string) ($item['key'] ?? ''),
                    'artworkIndex' => $index,
                ]),
            ];
        })->all();

        return [
            'key' => (string) ($item['key'] ?? ''),
            'product' => [
                'id' => isset($product['id']) ? (int) $product['id'] : null,
                'slug' => (string) ($product['slug'] ?? $item['product_slug'] ?? ''),
                'name' => (string) ($product['title'] ?? $product['short_title'] ?? ''),
                'sku' => (string) ($product['sku'] ?? ''),
                'category' => $product['category'] ?? null,
                'sport' => $product['sport'] ?? null,
                'image' => $product['image'] ?? null,
                'alt' => $product['alt'] ?? null,
            ],
            'quantity' => (int) ($item['quantity'] ?? 0),
            'quantity_min' => (int) ($item['quantity_min'] ?? 1),
            'quantity_max' => (int) ($item['quantity_max'] ?? 999),
            'customization' => $customization,
            'unit_price' => round((float) ($item['unit_price'] ?? 0), 4),
            'customization_unit_price' => round((float) ($item['customization_unit_price'] ?? 0), 4),
            'shipping_unit_price' => round((float) ($item['shipping_unit_price'] ?? 0), 4),
            'line_subtotal' => round((float) ($item['line_subtotal'] ?? 0), 2),
            'customization_total' => round((float) ($item['customization_total'] ?? 0), 2),
            'product_shipping_total' => round((float) ($item['product_shipping_total'] ?? 0), 2),
            'line_total' => round((float) ($item['line_total'] ?? 0), 2),
            'uses_product_shipping' => (bool) ($item['uses_product_shipping'] ?? false),
        ];
    }
}
