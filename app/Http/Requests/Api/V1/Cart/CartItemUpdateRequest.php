<?php

namespace App\Http\Requests\Api\V1\Cart;

use App\Http\Requests\Api\V1\ApiFormRequest;

final class CartItemUpdateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:999']];
    }
}
