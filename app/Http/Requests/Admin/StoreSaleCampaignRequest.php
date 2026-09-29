<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Support\PromotionBannerPlacement;
use App\Support\PublicUrl;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $submitAction = (string) $this->input('submit_action', '');
        $status = match ($submitAction) {
            'publish' => 'live',
            'draft' => 'draft',
            default => (string) $this->input('status', 'draft'),
        };

        $this->merge([
            'status' => $status,
            'repeat_weekdays' => filter_var($this->input('repeat_weekdays', false), FILTER_VALIDATE_BOOLEAN),
            'show_sale_badge' => filter_var($this->input('show_sale_badge', false), FILTER_VALIDATE_BOOLEAN),
            'show_sale_page' => filter_var($this->input('show_sale_page', false), FILTER_VALIDATE_BOOLEAN),
            'target_ids' => array_values(array_unique(array_map('intval', (array) $this->input('target_ids', [])))),
            'excluded_product_ids' => array_values(array_unique(array_map('intval', (array) $this->input('excluded_product_ids', [])))),
            'weekdays' => array_values(array_unique((array) $this->input('weekdays', []))),
            'remove_banner_image' => filter_var($this->input('remove_banner_image', false), FILTER_VALIDATE_BOOLEAN),
            'remove_banner_mobile_image' => filter_var($this->input('remove_banner_mobile_image', false), FILTER_VALIDATE_BOOLEAN),
            'banner_placements_present' => filter_var($this->input('banner_placements_present', false), FILTER_VALIDATE_BOOLEAN),
            'banner_placements' => array_values(array_unique((array) $this->input('banner_placements', []))),
            'banner_heading' => $this->filled('banner_heading') ? trim((string) $this->input('banner_heading')) : null,
            'banner_alt_text' => $this->filled('banner_alt_text') ? trim((string) $this->input('banner_alt_text')) : null,
            'banner_cta_label' => $this->filled('banner_cta_label') ? trim((string) $this->input('banner_cta_label')) : null,
            'banner_destination_link' => $this->filled('banner_destination_link') ? trim((string) $this->input('banner_destination_link')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'campaign_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'live'])],
            'submit_action' => ['nullable', Rule::in(['draft', 'publish'])],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'end_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'string', 'max:100', Rule::exists('time_zones', 'identifier')->where(fn ($query) => $query->where('is_active', true))],
            'repeat_weekdays' => ['required', 'boolean'],
            'weekdays' => ['array'],
            'weekdays.*' => [Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'applies_to' => ['required', Rule::in(['all', 'parent_categories', 'product_categories', 'subcategories', 'products'])],
            'target_ids' => ['array'],
            'target_ids.*' => ['integer', 'distinct'],
            'excluded_product_ids' => ['array'],
            'excluded_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'show_sale_badge' => ['required', 'boolean'],
            'show_sale_page' => ['required', 'boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000000'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:12288'],
            'banner_mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:12288'],
            'remove_banner_image' => ['required', 'boolean'],
            'remove_banner_mobile_image' => ['required', 'boolean'],
            'banner_placements_present' => ['required', 'boolean'],
            'banner_placements' => ['array'],
            'banner_placements.*' => ['required', 'string', 'distinct', Rule::in(PromotionBannerPlacement::ALL)],
            'banner_heading' => ['nullable', 'string', 'max:255'],
            'banner_alt_text' => ['nullable', 'string', 'max:255'],
            'banner_cta_label' => ['nullable', 'string', 'max:80'],
            'banner_destination_link' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filled($value) && ! PublicUrl::isAllowed($value)) {
                        $fail('The banner destination must be a safe relative path or a valid HTTP/HTTPS URL.');
                    }
                },
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value') > 100) {
                    $validator->errors()->add('discount_value', 'Percentage discount cannot exceed 100%.');
                }

                $timezone = (string) $this->input('timezone');
                $start = $this->dateTimeValue('start_date', 'start_time', $timezone);
                $end = $this->dateTimeValue('end_date', 'end_time', $timezone);
                if ($start !== null && $end !== null && $end <= $start) {
                    $validator->errors()->add('end_date', 'The campaign end date and time must be after the start date and time.');
                }

                if ($this->boolean('repeat_weekdays') && count((array) $this->input('weekdays', [])) === 0) {
                    $validator->errors()->add('weekdays', 'Select at least one weekday when weekly repeat is enabled.');
                }

                $appliesTo = (string) $this->input('applies_to');
                $targetIds = (array) $this->input('target_ids', []);

                if ($appliesTo !== 'all' && count($targetIds) === 0) {
                    $validator->errors()->add('target_ids', 'Select at least one campaign target.');
                    return;
                }

                if ($appliesTo === 'products') {
                    $validCount = Product::query()->whereIn('id', $targetIds)->count();
                    if ($validCount !== count($targetIds)) {
                        $validator->errors()->add('target_ids', 'One or more selected products are no longer available.');
                    }

                    $excludedIds = (array) $this->input('excluded_product_ids', []);
                    if (array_intersect($targetIds, $excludedIds) !== []) {
                        $validator->errors()->add('excluded_product_ids', 'A specifically selected product cannot also be excluded.');
                    }
                    return;
                }

                if ($appliesTo !== 'all') {
                    $query = Category::query()->whereIn('id', $targetIds);

                    if ($appliesTo === 'parent_categories') {
                        $query->whereNull('parent_id');
                    } elseif ($appliesTo === 'subcategories') {
                        $query->whereNotNull('parent_id')->whereHas('children');
                    } elseif ($appliesTo === 'product_categories') {
                        $query->whereDoesntHave('children');
                    }

                    if ($query->count() !== count($targetIds)) {
                        $validator->errors()->add('target_ids', 'One or more selected categories do not match the chosen target type.');
                    }
                }
            },
        ];
    }

    private function dateTimeValue(string $dateKey, string $timeKey, string $timezone): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable(
                $this->input($dateKey).' '.$this->input($timeKey),
                new DateTimeZone($timezone)
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
