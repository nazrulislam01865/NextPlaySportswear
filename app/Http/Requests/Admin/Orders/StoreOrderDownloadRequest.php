<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->canManageOrders() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:220'],
            'order_item_id' => ['nullable', 'integer'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,zip,txt,csv,png,jpg,jpeg,webp,svg,ai,eps'],
            'download_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'license_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
