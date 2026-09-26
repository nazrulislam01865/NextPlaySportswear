<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slug = trim((string) ($this->input('slug') ?: Str::slug($name)));

        $this->merge([
            'name' => $name,
            'slug' => Str::slug($slug),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        $gender = $this->route('gender');
        $id = is_object($gender) ? $gender->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('genders', 'name')->ignore($id)],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('genders', 'slug')->ignore($id)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers, and hyphens only, for example men or youth-unisex.',
        ];
    }
}
