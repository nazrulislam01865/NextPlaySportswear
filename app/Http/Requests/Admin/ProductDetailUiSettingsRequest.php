<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductDetailUiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.save_enabled' => ['nullable', 'boolean'],
            'settings.share_enabled' => ['nullable', 'boolean'],
            'settings.save_label' => ['nullable', 'string', 'max:80'],
            'settings.share_label' => ['nullable', 'string', 'max:80'],
            'settings.sample_label' => ['nullable', 'string', 'max:120'],
            'settings.sizes_label' => ['nullable', 'string', 'max:100'],
            'settings.sizes_available_label' => ['nullable', 'string', 'max:100'],
            'settings.minimum_order_label' => ['nullable', 'string', 'max:120'],
            'settings.size_guide_label' => ['nullable', 'string', 'max:120'],
            'settings.multiple_sizes_note' => ['nullable', 'string', 'max:500'],
            'settings.artwork_step_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_step_description' => ['nullable', 'string', 'max:300'],
            'settings.artwork_upload_tab_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_upload_tab_description' => ['nullable', 'string', 'max:300'],
            'settings.artwork_existing_tab_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_existing_tab_description' => ['nullable', 'string', 'max:300'],
            'settings.artwork_help_tab_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_help_tab_description' => ['nullable', 'string', 'max:300'],
            'settings.artwork_drop_title' => ['nullable', 'string', 'max:160'],
            'settings.artwork_browse_text' => ['nullable', 'string', 'max:120'],
            'settings.artwork_formats_label' => ['nullable', 'string', 'max:120'],
            'settings.artwork_max_size_label' => ['nullable', 'string', 'max:120'],
            'settings.artwork_multiple_files_text' => ['nullable', 'string', 'max:200'],
            'settings.artwork_next_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_next_lines' => ['nullable', 'string', 'max:1200'],
            'settings.artwork_help_next_title' => ['nullable', 'string', 'max:120'],
            'settings.artwork_help_next_lines' => ['nullable', 'string', 'max:1200'],
            'settings.production_step_title' => ['nullable', 'string', 'max:120'],
            'settings.production_step_description' => ['nullable', 'string', 'max:300'],
            'settings.production_lead_time_label' => ['nullable', 'string', 'max:120'],
            'settings.shipping_method_label' => ['nullable', 'string', 'max:120'],
            'settings.estimated_delivery_title' => ['nullable', 'string', 'max:120'],
            'settings.production_time_label' => ['nullable', 'string', 'max:120'],
            'settings.shipping_time_label' => ['nullable', 'string', 'max:120'],
            'settings.estimated_delivery_label' => ['nullable', 'string', 'max:120'],
            'settings.estimated_delivery_note' => ['nullable', 'string', 'max:200'],
            'settings.worldwide_shipping_title' => ['nullable', 'string', 'max:120'],
            'settings.worldwide_shipping_text' => ['nullable', 'string', 'max:600'],
            'settings.important_notes_title' => ['nullable', 'string', 'max:120'],
            'settings.important_notes_lines' => ['nullable', 'string', 'max:1200'],
            'icon_files' => ['nullable', 'array'],
            'icon_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,avif,svg', 'mimetypes:image/jpeg,image/png,image/webp,image/avif,image/svg+xml', 'max:2048'],
            'clear_icons' => ['nullable', 'array'],
            'clear_icons.*' => ['nullable', 'boolean'],
        ];
    }
}
