<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

abstract class ReviewConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
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
