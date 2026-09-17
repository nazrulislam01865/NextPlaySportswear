<?php

namespace App\Http\Requests\Api\V1\Cart;

use App\Http\Requests\Api\V1\ApiFormRequest;

final class CouponApplyRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['coupon_code' => ['required', 'string', 'max:80']];
    }
}
