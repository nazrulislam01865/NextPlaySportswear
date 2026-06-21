<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ReviewConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirm_details' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_details.accepted' => 'Please confirm that the order details are correct before continuing.',
        ];
    }
}
