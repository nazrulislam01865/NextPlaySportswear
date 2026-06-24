<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['card', 'paypal', 'saved_card', 'invoice'])],
            'saved_payment_method_id' => [
                Rule::requiredIf($this->input('payment_method') === 'saved_card'),
                'nullable',
                'integer',
                Rule::exists('customer_payment_methods', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id),
                ),
            ],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }
}
