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
            'order_number' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }
}
