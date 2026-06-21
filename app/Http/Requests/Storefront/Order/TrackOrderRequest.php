<?php

namespace App\Http\Requests\Storefront\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_number.regex' => 'Enter a valid order number using letters, numbers, and hyphens only.',
        ];
    }
}
