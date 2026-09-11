<?php

namespace App\Http\Requests\Admin;

use App\Models\BulkQuoteRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBulkQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin')?->canAdmin('orders.manage');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(BulkQuoteRequest::statuses()))],
            'priority' => ['required', 'string', Rule::in(array_keys(BulkQuoteRequest::priorities()))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'quote_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
            'last_contacted_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assigned_to' => $this->filled('assigned_to') ? $this->input('assigned_to') : null,
            'quoted_amount' => $this->filled('quoted_amount') ? $this->input('quoted_amount') : null,
            'quote_currency' => strtoupper(trim((string) $this->input('quote_currency', 'USD'))),
            'admin_note' => $this->filled('admin_note') ? trim((string) $this->input('admin_note')) : null,
            'last_contacted_at' => $this->filled('last_contacted_at') ? $this->input('last_contacted_at') : null,
        ]);
    }
}
