<?php

namespace App\Http\Requests\Admin\Orders;

use App\Support\Payments\AdminFinancialWriteGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            ...AdminFinancialWriteGuard::requestRules(),
        ];
    }
}
