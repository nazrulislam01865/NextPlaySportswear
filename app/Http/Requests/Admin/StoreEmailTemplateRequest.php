<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:email_templates,key'],
            'trigger_event' => ['required_without:clone_from', 'nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'preset' => ['nullable', 'string', 'in:blank,order,shipment,delivery,security,reward,welcome,clone'],
            'clone_from' => ['required_if:preset,clone', 'nullable', 'string', 'exists:email_templates,key'],
            'subject' => ['nullable', 'string', 'max:200'],
            'heading' => ['nullable', 'string', 'max:200'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:100'],
            'cta_custom_url' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'in:draft,publish'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The template name is required.',
            'key.unique' => 'A template with this key already exists.',
            'key.regex' => 'The template key must contain only lowercase letters, numbers, and hyphens (e.g. vip-order-notice).',
            'trigger_event.required' => 'The trigger/event name is required.',
            'clone_from.exists' => 'The selected template to clone from was not found.',
        ];
    }
}
