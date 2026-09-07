<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:100'],
        ];
    }
}
