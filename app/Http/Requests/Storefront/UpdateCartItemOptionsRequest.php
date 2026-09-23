<?php

namespace App\Http\Requests\Storefront;

class UpdateCartItemOptionsRequest extends AddCartItemRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'retained_artwork_json' => ['nullable', 'json', 'max:100000'],
        ]);
    }
}
