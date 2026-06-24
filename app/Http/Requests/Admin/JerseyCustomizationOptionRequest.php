<?php

namespace App\Http\Requests\Admin;

use App\Enums\JerseyCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JerseyCustomizationOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $option = $this->route('jerseyCustomizationOption');

        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(JerseyCustomizationType::class)],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('jersey_customization_options', 'slug')
                    ->where(fn ($query) => $query->where('type', $this->input('type')))
                    ->ignore($option?->getKey()),
            ],
            'color_hex' => [
                Rule::requiredIf($this->input('type') === JerseyCustomizationType::Color->value),
                'nullable',
                'regex:/^#[0-9A-F]{6}$/',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],

            'images' => ['nullable', 'array', 'max:20'],
            'images.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'images.*.name' => ['nullable', 'string', 'max:180'],
            'images.*.image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
            'images.*.image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'images.*.is_primary' => ['required', 'boolean'],
            'images.*.sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $primaryCount = 0;

            foreach ((array) $this->input('images', []) as $index => $image) {
                $uploaded = $this->file("images.{$index}.image_file");
                $existingId = (int) ($image['existing_id'] ?? 0);
                $url = trim((string) ($image['image_url'] ?? ''));
                $name = trim((string) ($image['name'] ?? ''));

                if ($existingId === 0 && ! $uploaded && $url === '' && $name === '') {
                    continue;
                }

                if ($name === '') {
                    $validator->errors()->add("images.{$index}.name", 'Each image needs a name.');
                }

                if ($existingId === 0 && ! $uploaded && $url === '') {
                    $validator->errors()->add(
                        "images.{$index}.image_file",
                        'Upload an image or provide an image link.'
                    );
                }

                if ($uploaded && $url !== '') {
                    $validator->errors()->add(
                        "images.{$index}.image_url",
                        'Use either an uploaded image or an image link for the same row, not both.'
                    );
                }

                if (filter_var($image['is_primary'] ?? false, FILTER_VALIDATE_BOOL)) {
                    $primaryCount++;
                }
            }

            if ($primaryCount > 1) {
                $validator->errors()->add('images', 'Only one image can be selected as primary.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->input('type');
        $hex = strtoupper(ltrim(trim((string) $this->input('color_hex')), '#'));
        $option = $this->route('jerseyCustomizationOption');

        $images = collect($this->input('images', []))
            ->map(function (mixed $image, int $index): array {
                $row = is_array($image) ? $image : [];

                return [
                    'existing_id' => filled($row['existing_id'] ?? null) ? (int) $row['existing_id'] : null,
                    'name' => trim((string) ($row['name'] ?? '')),
                    'image_url' => trim((string) ($row['image_url'] ?? '')),
                    'is_primary' => filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOL),
                    'sort_order' => is_numeric($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('slug', $this->input('name'))),
            'color_hex' => $type === JerseyCustomizationType::Color->value && $hex !== '' ? '#'.$hex : null,
            'description' => filled($this->input('description'))
                ? trim((string) $this->input('description'))
                : null,
            'is_active' => true,
            'sort_order' => $option instanceof JerseyCustomizationOption
                ? (int) $option->sort_order
                : 0,
            'images' => $images,
        ]);
    }
}
