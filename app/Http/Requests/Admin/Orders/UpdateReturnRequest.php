<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->canManageOrders() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(config('commerce.return_statuses', [])))],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'approved_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'refund_status' => ['nullable', Rule::in(array_keys(config('commerce.refund_statuses', [])))],
            'provider_reference' => ['nullable', 'string', 'max:190'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $returnRequest = $this->route('returnRequest');
            if (! $returnRequest?->order) {
                return;
            }

            $amount = (float) $this->input('approved_amount', 0);
            if ($amount > (float) $returnRequest->order->grand_total) {
                $validator->errors()->add('approved_amount', 'The approved amount cannot exceed the order total.');
            }

            if ($returnRequest->type === 'exchange' && $amount > 0) {
                $validator->errors()->add('approved_amount', 'Approved refund amount is only available for return requests.');
            }
        }];
    }
}
