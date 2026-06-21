<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'order_type' => ['required', Rule::in(['personal', 'team', 'school', 'corporate'])],
            'delivery_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'proof_preference' => ['required', Rule::in(['proof_required', 'use_artwork', 'contact_first'])],
            'order_note' => ['nullable', 'string', 'max:1200'],
        ];
    }
}
