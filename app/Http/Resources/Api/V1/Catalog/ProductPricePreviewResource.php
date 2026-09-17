<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\ApiResource;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;

final class ProductPricePreviewResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $item = (array) ($this->resource['item'] ?? []);

        return [
            'product' => (array) ($this->resource['product'] ?? []),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'quantity_min' => (int) ($item['quantity_min'] ?? 1),
            'quantity_max' => (int) ($item['quantity_max'] ?? 999),
            'configuration' => (array) data_get($item, 'customization.configuration', []),
            'customization' => [
                'size_summary' => data_get($item, 'customization.size_summary'),
                'size_breakdown' => array_values((array) data_get($item, 'customization.size_breakdown', [])),
                'roster_fields' => array_values((array) data_get($item, 'customization.roster_fields', [])),
                'sample' => (array) data_get($item, 'customization.sample', []),
                'fulfillment' => (array) data_get($item, 'customization.fulfillment', []),
            ],
            'pricing' => [
                'currency' => (string) data_get($this->resource, 'product.currency', 'USD'),
                'unit_price' => round((float) ($item['unit_price'] ?? 0), 4),
                'customization_unit_price' => round((float) ($item['customization_unit_price'] ?? 0), 4),
                'shipping_unit_price' => round((float) ($item['shipping_unit_price'] ?? 0), 4),
                'line_subtotal' => round((float) ($item['line_subtotal'] ?? 0), 2),
                'customization_total' => round((float) ($item['customization_total'] ?? 0), 2),
                'product_shipping_total' => round((float) ($item['product_shipping_total'] ?? 0), 2),
                'line_total' => round((float) ($item['line_total'] ?? 0), 2),
            ],
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => ['authoritative' => true, 'contract' => 'product-price-preview-v1'],
            'request_id' => RequestId::from($request),
        ];
    }
}
