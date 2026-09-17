<?php

namespace App\Http\Requests\Concerns;

trait HasCartItemRules
{
    /** @return array<string, array<int, mixed>> */
    protected function cartItemRules(bool $includeRetainedArtwork = false): array
    {
        $rules = [
            'product_slug' => ['required', 'string', 'max:180'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'design_option' => ['nullable', 'string', 'max:80'],
            'delivery_preference' => ['nullable', 'string', 'max:80'],
            'size_summary' => ['nullable', 'string', 'max:600'],
            'artwork_status' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'configuration_json' => ['nullable', 'json', 'max:1000000'],
            'configuration' => ['nullable', 'array'],
            'artwork_files' => ['nullable', 'array', 'max:12'],
            'artwork_files.*' => ['file', 'mimes:pdf,svg,png,jpg,jpeg,webp', 'max:25600'],
            // Backward compatibility with older cached product forms.
            'artwork_file' => ['nullable', 'file', 'mimes:pdf,svg,png,jpg,jpeg,webp', 'max:25600'],
        ];

        if ($includeRetainedArtwork) {
            $rules['retained_artwork_json'] = ['nullable', 'json', 'max:100000'];
            $rules['retained_artwork_tokens'] = ['nullable', 'array', 'max:12'];
            $rules['retained_artwork_tokens.*'] = ['string', 'size:64'];
        }

        return $rules;
    }
}
