<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExchangeOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('update', $this->route('order')) ?? false)
            && $this->route('order')->canRequestExchange();
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::in(['size_issue', 'wrong_item', 'defective', 'roster_correction', 'other'])],
            'exchange_notes' => ['required', 'string', 'max:2500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:999'],
            'items.*.condition' => ['nullable', Rule::in(['unused', 'opened', 'worn_once', 'damaged'])],
            'items.*.replacement' => ['nullable', 'string', 'max:800'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,heic,heif'],
            'confirm_timeline' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $selected = collect($this->input('items', []))->filter(
                fn (array $item): bool => (int) ($item['quantity'] ?? 0) > 0,
            );

            if ($selected->isEmpty()) {
                $validator->errors()->add('items', 'Select at least one item to exchange.');
            }

            foreach ($selected as $index => $item) {
                if (blank($item['condition'] ?? null)) {
                    $validator->errors()->add("items.$index.condition", 'Condition is required for each selected item.');
                }
                if (blank($item['replacement'] ?? null)) {
                    $validator->errors()->add("items.$index.replacement", 'Describe the replacement required for each selected item.');
                }
            }
        }];
    }
}
