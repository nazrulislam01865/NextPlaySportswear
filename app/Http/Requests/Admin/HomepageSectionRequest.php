<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use App\Support\HomepageSectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HomepageSectionRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:3000'],
            'primary_label' => ['nullable', 'string', 'max:160'],
            'primary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'secondary_label' => ['nullable', 'string', 'max:160'],
            'secondary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'],
            'image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'mobile_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'],
            'mobile_image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'mobile_image_alt' => ['nullable', 'string', 'max:255'],
            'remove_mobile_image' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.icon' => ['nullable', 'string', 'max:20'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.label' => ['nullable', 'string', 'max:160'],
            'items.*.image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.image_alt' => ['nullable', 'string', 'max:255'],
            'items.*.category_id' => ['nullable', 'integer', 'distinct', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'remove_mobile_image' => $this->boolean('remove_mobile_image'),
            'primary_url' => trim((string) $this->input('primary_url', '')) ?: null,
            'secondary_url' => trim((string) $this->input('secondary_url', '')) ?: null,
            'image_url' => trim((string) $this->input('image_url', '')) ?: null,
            'mobile_image_url' => trim((string) $this->input('mobile_image_url', '')) ?: null,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = (string) $this->route('key');
            $definition = HomepageSectionRegistry::definition($key);
            $fields = $definition['fields'] ?? [];

            if (in_array('buttons', $fields, true)) {
                if (filled($this->input('primary_label')) && blank($this->input('primary_url'))) {
                    $validator->errors()->add('primary_url', 'Enter the primary button destination or remove the label.');
                }

                if (filled($this->input('secondary_label')) && blank($this->input('secondary_url'))) {
                    $validator->errors()->add('secondary_url', 'Enter the secondary button destination or remove the label.');
                }
            }
        });
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->safe()->except(['image_file', 'image_url', 'remove_image', 'mobile_image_file', 'mobile_image_url', 'remove_mobile_image']);

        foreach (['eyebrow', 'title', 'description', 'primary_label', 'primary_url', 'secondary_label', 'secondary_url', 'image_alt', 'mobile_image_alt'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field])) ?: null;
            }
        }

        $data['items'] = $this->cleanItems((array) $this->input('items', []));
        $data['is_active'] = $this->boolean('is_active');
        $data['sort_order'] = (int) $this->input('sort_order', 0);

        return $data;
    }

    /** @return array<int, array<string, string>> */
    private function cleanItems(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];
            foreach (['icon', 'title', 'subtitle', 'description', 'url', 'label', 'image_url', 'image_alt'] as $field) {
                $value = trim(strip_tags((string) ($item[$field] ?? '')));
                if ($value !== '') {
                    $row[$field] = $value;
                }
            }

            $categoryId = (int) ($item['category_id'] ?? 0);
            if ($categoryId > 0) {
                $row['category_id'] = $categoryId;
            }

            if ($row !== []) {
                $clean[] = $row;
            }
        }

        return $clean;
    }
}
