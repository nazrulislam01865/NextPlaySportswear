<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('update', $this->route('order')) ?? false)
            && $this->route('order')->canRequestCancellation();
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['entire_order', 'selected_items'])],
            'reason_code' => ['required', Rule::in(['ordered_by_mistake', 'deadline_changed', 'duplicate_order', 'payment_issue', 'size_or_roster_change', 'other'])],
            'reason' => ['nullable', 'string', 'max:1500'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:0', 'max:999'],
        ];
    }
}
