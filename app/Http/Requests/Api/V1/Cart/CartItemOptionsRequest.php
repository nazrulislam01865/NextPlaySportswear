<?php

namespace App\Http\Requests\Api\V1\Cart;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Concerns\HasCartItemRules;

final class CartItemOptionsRequest extends ApiFormRequest
{
    use HasCartItemRules;

    public function rules(): array
    {
        return $this->cartItemRules(true);
    }
}
