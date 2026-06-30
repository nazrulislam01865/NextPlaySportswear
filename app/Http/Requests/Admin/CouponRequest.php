<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'minimum_subtotal' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'required_if:discount_type,percentage'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Coupon code may contain only letters, numbers, dashes, and underscores.',
            'maximum_discount.required_if' => 'Set a maximum discount for percentage coupons so large carts remain controlled.',
            'expires_at.after_or_equal' => 'Expiry date must be after the start date.',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['code'] = strtoupper(trim((string) $data['code']));
        $data['minimum_subtotal'] = $data['minimum_subtotal'] ?? 0;
        $data['maximum_discount'] = $data['maximum_discount'] ?? null;
        $data['usage_limit'] = $data['usage_limit'] ?? null;
        $data['usage_limit_per_customer'] = $data['usage_limit_per_customer'] ?? null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
