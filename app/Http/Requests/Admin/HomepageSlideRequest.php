<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HomepageSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],

            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240', 'dimensions:min_width=800,min_height=350,max_width=8000,max_height=5000'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'image_focal_position' => ['required', Rule::in(['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])],

            'show_content' => ['nullable', 'boolean'],
            'show_eyebrow' => ['nullable', 'boolean'],
            'show_title' => ['nullable', 'boolean'],
            'show_description' => ['nullable', 'boolean'],

            'show_primary_button' => ['nullable', 'boolean'],
            'primary_label' => ['nullable', 'string', 'max:160'],
            'primary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'primary_target' => ['required', Rule::in(['_self', '_blank'])],

            'show_secondary_button' => ['nullable', 'boolean'],
            'secondary_label' => ['nullable', 'string', 'max:160'],
            'secondary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'secondary_target' => ['required', Rule::in(['_self', '_blank'])],

            'content_position' => ['required', Rule::in(['left', 'center', 'right'])],
            'text_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
            'text_theme' => ['required', Rule::in(['light', 'dark'])],
            'overlay_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'overlay_opacity' => ['required', 'integer', 'min:0', 'max:100'],

            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'show_content', 'show_eyebrow', 'show_title', 'show_description',
            'show_primary_button', 'show_secondary_button', 'is_active', 'remove_image',
        ];

        $payload = [];
        foreach ($booleanFields as $field) {
            $payload[$field] = $this->boolean($field);
        }

        $payload['overlay_color'] = strtoupper(trim((string) $this->input('overlay_color', '#0D2545')));
        $payload['image_url'] = trim((string) $this->input('image_url', '')) ?: null;
        $payload['primary_url'] = trim((string) $this->input('primary_url', '')) ?: null;
        $payload['secondary_url'] = trim((string) $this->input('secondary_url', '')) ?: null;

        $this->merge($payload);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $slide = $this->route('homepage_slide') ?? $this->route('homepageSlide');
            $hasExistingImage = $slide && filled($slide->image_path ?: $slide->image_url);
            $willRemove = $this->boolean('remove_image');
            $hasNewImage = $this->hasFile('image_file') || filled($this->input('image_url'));

            if ((! $hasExistingImage || $willRemove) && ! $hasNewImage) {
                $validator->errors()->add('image_file', 'Upload a slider image or provide a secure HTTPS image URL.');
            }

            if (filled($this->input('starts_at')) && filled($this->input('ends_at'))
                && strtotime((string) $this->input('ends_at')) <= strtotime((string) $this->input('starts_at'))) {
                $validator->errors()->add('ends_at', 'The end time must be later than the start time.');
            }

            if ($this->boolean('show_content')) {
                if ($this->boolean('show_title') && blank($this->input('title'))) {
                    $validator->errors()->add('title', 'Enter a title or turn off the title display.');
                }

                if ($this->boolean('show_eyebrow') && blank($this->input('eyebrow'))) {
                    $validator->errors()->add('eyebrow', 'Enter eyebrow text or turn off the eyebrow display.');
                }

                if ($this->boolean('show_description') && blank($this->input('description'))) {
                    $validator->errors()->add('description', 'Enter a description or turn off the description display.');
                }

                if ($this->boolean('show_primary_button')) {
                    if (blank($this->input('primary_label'))) {
                        $validator->errors()->add('primary_label', 'Enter the primary button label.');
                    }
                    if (blank($this->input('primary_url'))) {
                        $validator->errors()->add('primary_url', 'Enter the primary button destination.');
                    }
                }

                if ($this->boolean('show_secondary_button')) {
                    if (blank($this->input('secondary_label'))) {
                        $validator->errors()->add('secondary_label', 'Enter the secondary button label.');
                    }
                    if (blank($this->input('secondary_url'))) {
                        $validator->errors()->add('secondary_url', 'Enter the secondary button destination.');
                    }
                }
            }
        });
    }
}
