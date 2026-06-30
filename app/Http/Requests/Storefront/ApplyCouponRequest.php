<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_code.required' => 'Enter a promo code before applying.',
            'coupon_code.regex' => 'Promo code may contain only letters, numbers, dashes, and underscores.',
            'coupon_code.max' => 'Promo code cannot be longer than 60 characters.',
        ];
    }
}
