<?php

namespace App\Http\Requests\Storefront;

class UpdateCartItemOptionsRequest extends AddCartItemRequest
{
    public function rules(): array
    {
        return $this->cartItemRules(true);
    }
}
