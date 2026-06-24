<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->canManageOrders() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(config('commerce.order_statuses', [])))],
            'payment_status' => ['required', Rule::in(array_keys(config('commerce.payment_statuses', [])))],
            'fulfillment_status' => ['required', Rule::in(array_keys(config('commerce.fulfillment_statuses', [])))],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
