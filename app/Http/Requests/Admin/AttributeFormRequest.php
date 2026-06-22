<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $attributeId = $this->route('attribute')?->getKey();

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('attributes', 'slug')->ignore($attributeId)],
            'display_type' => ['required', Rule::in(['checkbox', 'radio', 'select', 'color', 'image', 'range'])],
            'unit' => ['nullable', 'string', 'max:40'],
            'is_filterable' => ['required', 'boolean'],
            'is_searchable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'values' => ['nullable', 'array', 'max:250'],
            'values.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'values.*.label' => ['nullable', 'string', 'max:180'],
            'values.*.slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'values.*.color_hex' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'values.*.image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'values.*.image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'values.*.numeric_value' => ['nullable', 'numeric', 'between:-9999999999,9999999999'],
            'values.*.is_active' => ['required', 'boolean'],
            'values.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = collect($this->input('values', []))->map(function (array $value): array {
            $label = trim((string) ($value['label'] ?? ''));
            $value['slug'] = Str::slug((string) ($value['slug'] ?? $label));
            $value['color_hex'] = $this->normalizeHex($value['color_hex'] ?? null);
            $value['is_active'] = filter_var($value['is_active'] ?? false, FILTER_VALIDATE_BOOL);
            return $value;
        })->all();

        $this->merge([
            'slug' => Str::slug((string) $this->input('slug', $this->input('name'))),
            'is_filterable' => $this->boolean('is_filterable'),
            'is_searchable' => $this->boolean('is_searchable'),
            'is_active' => $this->boolean('is_active'),
            'values' => $values,
        ]);
    }

    private function normalizeHex(mixed $value): ?string
    {
        $hex = strtoupper(ltrim(trim((string) $value), '#'));
        if ($hex === '') {
            return null;
        }
        if (preg_match('/^[0-9A-F]{3}$/', $hex)) {
            $hex = implode('', array_map(static fn (string $character): string => $character.$character, str_split($hex)));
        }
        return preg_match('/^[0-9A-F]{6}$/', $hex) ? '#'.$hex : (string) $value;
    }
}
