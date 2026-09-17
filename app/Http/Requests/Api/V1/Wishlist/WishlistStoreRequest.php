<?php

namespace App\Http\Requests\Api\V1\Wishlist;

use App\Http\Requests\Api\V1\ApiFormRequest;

final class WishlistStoreRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['product_id' => ['required', 'integer', 'min:1']];
    }
}
