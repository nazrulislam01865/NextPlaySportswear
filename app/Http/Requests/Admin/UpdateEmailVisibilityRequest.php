<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'show_logo' => ['nullable', 'boolean'],
            'show_greeting' => ['nullable', 'boolean'],
            'show_previous_estimate' => ['nullable', 'boolean'],
            'show_updated_estimate' => ['nullable', 'boolean'],
            'show_holiday_reason' => ['nullable', 'boolean'],
            'show_delivery_card' => ['nullable', 'boolean'],
            'show_order_number' => ['nullable', 'boolean'],
            'show_cta_button' => ['nullable', 'boolean'],
            'show_support_contact' => ['nullable', 'boolean'],
            'show_social_links' => ['nullable', 'boolean'],
            'show_footer_note' => ['nullable', 'boolean'],
            'action' => ['required', 'string', 'in:draft,publish'],
        ];
    }
}
