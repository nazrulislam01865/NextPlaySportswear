<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Concerns\HasCartItemRules;

final class ProductPricePreviewRequest extends ApiFormRequest
{
    use HasCartItemRules;

    public function rules(): array
    {
        $rules = $this->cartItemRules();
        unset($rules['artwork_files'], $rules['artwork_files.*'], $rules['artwork_file']);

        // The route slug is authoritative. Clients do not need to repeat it.
        $rules['product_slug'] = ['nullable', 'string', 'max:180'];

        return $rules;
    }

    /** @return array<string, mixed> */
    public function previewPayload(string $slug): array
    {
        return array_merge($this->validated(), ['product_slug' => $slug]);
    }
}
