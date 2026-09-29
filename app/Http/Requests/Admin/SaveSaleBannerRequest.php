<?php

namespace App\Http\Requests\Admin;

use App\Models\SaleBanner;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'sale_campaign_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->boolean('inherit_campaign_schedule')),
                'integer',
                'exists:sale_campaigns,id',
            ],
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
                    if (! PublicUrl::isAllowed((string) $value)) {
                        $fail('The destination link must be a safe relative path or a valid HTTP/HTTPS URL.');
                    }
                },
            ],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*' => ['required', 'string', 'distinct', Rule::in(SaleBanner::PLACEMENTS)],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('inherit_campaign_schedule')) {
                    return;
                }

                try {
                    $start = CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        (string) $this->input('start_date').' '.(string) $this->input('start_time'),
                        (string) $this->input('timezone')
                    );
                    $end = CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        (string) $this->input('end_date').' '.(string) $this->input('end_time'),
                        (string) $this->input('timezone')
                    );

                    if ($start && $end && $end->lessThanOrEqualTo($start)) {
                        $validator->errors()->add('end_date', 'The banner end date and time must be after the start date and time.');
                    }
                } catch (\Throwable) {
                    // Field-level date/timezone validation will report malformed input.
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inherit_campaign_schedule' => $this->boolean('inherit_campaign_schedule'),
            'remove_mobile_image' => $this->boolean('remove_mobile_image'),
            'placements' => array_values(array_unique((array) $this->input('placements', []))),
            'destination_link' => trim((string) $this->input('destination_link', '')),
        ]);
    }
}
