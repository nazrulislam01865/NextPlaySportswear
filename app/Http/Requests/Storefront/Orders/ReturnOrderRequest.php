<?php

namespace App\Http\Requests\Storefront\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReturnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('update', $this->route('order')) ?? false)
            && $this->route('order')->canRequestReturn();
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::in(['wrong_item', 'damaged', 'quality_issue', 'size_issue', 'not_as_expected', 'other'])],
            'requested_resolution' => ['required', Rule::in(['refund', 'store_credit'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:999'],
            'items.*.condition' => ['nullable', Rule::in(['unused', 'opened', 'worn_once', 'damaged'])],
            'items.*.note' => ['nullable', 'string', 'max:600'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,heic,heif'],
            'confirm_accuracy' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $selected = collect($this->input('items', []))->filter(
                fn (array $item): bool => (int) ($item['quantity'] ?? 0) > 0,
            );

            if ($selected->isEmpty()) {
                $validator->errors()->add('items', 'Select at least one item to return.');
            }

            foreach ($selected as $index => $item) {
                if (blank($item['condition'] ?? null)) {
                    $validator->errors()->add("items.$index.condition", 'Condition is required for each selected item.');
                }
            }
        }];
    }
}
