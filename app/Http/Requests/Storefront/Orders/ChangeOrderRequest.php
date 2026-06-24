<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('update', $this->route('order')) ?? false)
            && $this->route('order')->canRequestChange();
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::in([
                'sizes',
                'roster',
                'artwork',
                'shipping_address',
                'delivery_deadline',
                'quantity',
                'other',
            ])],
            'requested_changes' => ['required', 'string', 'max:3000'],
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['integer'],
        ];
    }
}
