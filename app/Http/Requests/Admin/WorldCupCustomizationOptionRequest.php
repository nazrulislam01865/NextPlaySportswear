<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorldCupCustomizationType;
use App\Models\WorldCupCustomizationOption;
use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorldCupCustomizationOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isAdmin() === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $option = $this->route('worldCupCustomizationOption');

        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(array_keys(WorldCupCustomizationType::options()))],
            'slug' => [
                'required', 'string', 'max:180',
                Rule::unique('world_cup_customization_options', 'slug')
                    ->where(fn ($query) => $query
                        ->where('category_key', $this->input('category_key'))
                        ->where('type', $this->input('type')))
                    ->ignore($option?->getKey()),
            ],
            'category_key' => ['required', 'string', 'max:80'],
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
                    $validator->errors()->add("images.{$index}.image_file", 'Upload an image or provide an image link.');
                }
                if ($uploaded && $url !== '') {
                    $validator->errors()->add("images.{$index}.image_url", 'Use either an uploaded image or an image link for the same row, not both.');
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
        $type = WorldCupCustomizationType::tryFrom((string) $this->input('type'));
        $option = $this->route('worldCupCustomizationOption');
        $name = trim((string) $this->input('name'));
        $images = collect($this->input('images', []))->map(function (mixed $image, int $index) use ($name): array {
            $row = is_array($image) ? $image : [];
            $existingId = filled($row['existing_id'] ?? null) ? (int) $row['existing_id'] : null;
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            $hasUpload = $this->hasFile("images.{$index}.image_file");
            $imageName = trim((string) ($row['name'] ?? ''));
            if ($imageName === '' && ($existingId || $imageUrl !== '' || $hasUpload)) {
                $imageName = $name;
            }
            return [
                'existing_id' => $existingId,
                'name' => $imageName,
                'image_url' => $imageUrl,
                'is_primary' => filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOL),
                'sort_order' => is_numeric($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index,
            ];
        })->values()->all();

        $this->merge([
            'name' => $name,
            'slug' => Str::slug((string) $this->input('slug', $name)),
            'category_key' => $type?->categoryKey(),
            'description' => $type?->usesDescription() === true && filled($this->input('description'))
                ? trim((string) $this->input('description'))
                : null,
            'is_active' => true,
            'sort_order' => $option instanceof WorldCupCustomizationOption ? (int) $option->sort_order : 0,
            'images' => $images,
        ]);
    }
}
