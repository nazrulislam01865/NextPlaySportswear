<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;

use Illuminate\Foundation\Http\FormRequest;

class ContactPageRequest extends FormRequest
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
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],

            'info.heading' => ['required', 'string', 'max:255'],
            'info.address_title' => ['required', 'string', 'max:255'],
            'info.address' => ['required', 'string', 'max:1000'],
            'info.email_title' => ['required', 'string', 'max:255'],
            'info.email' => ['required', 'string', 'max:255'],
            'info.whatsapp_title' => ['required', 'string', 'max:255'],
            'info.whatsapp_number' => ['required', 'string', 'max:100'],
            'info.whatsapp_link_text' => ['required', 'string', 'max:100'],
            'info.whatsapp_url' => ['required', 'string', 'max:255', new SafePublicUrl()],
            'info.follow_heading' => ['required', 'string', 'max:255'],
            'info.instagram_url' => ['nullable', 'string', 'max:255', new SafePublicUrl()],
            'info.facebook_url' => ['nullable', 'string', 'max:255', new SafePublicUrl()],
            'info.youtube_url' => ['nullable', 'string', 'max:255', new SafePublicUrl()],
            'info.footer_note' => ['required', 'string', 'max:1000'],

            'form.heading' => ['required', 'string', 'max:255'],
            'form.subheading' => ['required', 'string', 'max:500'],
            'form.submit_btn_text' => ['required', 'string', 'max:100'],
            'form.privacy_notice' => ['required', 'string', 'max:1000'],

            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

