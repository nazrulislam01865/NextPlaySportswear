<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;

class HeaderTopbarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin')?->canAdmin('header_topbar.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'announcement_text' => ['required', 'string', 'max:180'],
            'track_order_label' => ['required', 'string', 'max:50'],
            'whatsapp_label' => ['required', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:60', 'regex:/^[0-9+().\s-]*$/'],
            'whatsapp_url' => ['nullable', 'string', 'max:500', new SafePublicUrl()],
            'follow_label' => ['required', 'string', 'max:50'],
            'background_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'links_gap_px' => ['nullable', 'integer', 'between:4,40'],
        ];

        foreach (['bar', 'announcement', 'track_order', 'whatsapp', 'instagram', 'facebook', 'tiktok', 'youtube'] as $item) {
            $rules[$item.'_enabled'] = ['required', 'boolean'];
        }

        foreach (['instagram', 'facebook', 'tiktok', 'youtube'] as $network) {
            $rules[$network.'_url'] = ['nullable', 'string', 'max:500', new SafePublicUrl()];
        }

        return $rules;
    }
}
