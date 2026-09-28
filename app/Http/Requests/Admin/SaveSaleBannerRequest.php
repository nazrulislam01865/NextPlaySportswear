<?php

namespace App\Http\Requests\Admin;

use App\Models\SaleBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSaleBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $editing = $this->route('saleBanner') instanceof SaleBanner;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sale_campaign_id' => ['nullable', 'integer', 'exists:sale_campaigns,id'],
            'desktop_image' => [$editing ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:12288'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:12288'],
            'remove_mobile_image' => ['nullable', 'boolean'],
            'alt_text' => ['required', 'string', 'max:255'],
            'heading' => ['required', 'string', 'max:255'],
            'cta_label' => ['required', 'string', 'max:80'],
            'destination_link' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $link = trim((string) $value);
                    if (! str_starts_with($link, '/') && filter_var($link, FILTER_VALIDATE_URL) === false) {
                        $fail('The destination link must be a relative path beginning with / or a valid full URL.');
                    }
                },
            ],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*' => ['required', 'string', Rule::in(SaleBanner::PLACEMENTS)],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'inherit_campaign_schedule' => ['required', 'boolean'],
            'timezone' => [
                'required',
                'string',
                'max:100',
                Rule::exists('time_zones', 'identifier')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'start_date' => ['nullable', Rule::requiredIf(fn (): bool => ! $this->boolean('inherit_campaign_schedule')), 'date_format:Y-m-d'],
            'start_time' => ['nullable', Rule::requiredIf(fn (): bool => ! $this->boolean('inherit_campaign_schedule')), 'date_format:H:i'],
            'end_date' => ['nullable', Rule::requiredIf(fn (): bool => ! $this->boolean('inherit_campaign_schedule')), 'date_format:Y-m-d'],
            'end_time' => ['nullable', Rule::requiredIf(fn (): bool => ! $this->boolean('inherit_campaign_schedule')), 'date_format:H:i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inherit_campaign_schedule' => $this->boolean('inherit_campaign_schedule'),
            'remove_mobile_image' => $this->boolean('remove_mobile_image'),
            'placements' => array_values(array_unique((array) $this->input('placements', []))),
        ]);
    }
}
