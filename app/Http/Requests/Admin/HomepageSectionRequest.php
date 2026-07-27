<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\SafePublicUrl;
use App\Support\HomepageSectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
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
            'hero_slides' => ['nullable', 'array', 'max:12'],
            'hero_slides.*.id' => ['nullable', 'string', 'max:80'],
            'hero_slides.*.image_path' => ['nullable', 'string', 'max:2048'],
            'hero_slides.*.image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'hero_slides.*.image_alt' => ['nullable', 'string', 'max:255'],
            'hero_slides.*.image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'],
            'items' => ['nullable', 'array'],
            'items.*.icon' => ['nullable', 'string', 'max:20'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.label' => ['nullable', 'string', 'max:160'],
            'items.*.image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.image_alt' => ['nullable', 'string', 'max:255'],
            // Duplicate category selections are checked below so the user sees
            // the real category names instead of technical array field names.
            'items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'hero_slides.array' => 'The hero slider image list could not be read. Please refresh the page and try again.',
            'hero_slides.max' => 'The hero slider can contain up to 12 images.',
            'hero_slides.*.image_file.image' => 'Each hero slider upload must be a valid image.',
            'hero_slides.*.image_file.mimes' => 'Hero slider images must be JPG, PNG, WebP, or AVIF files.',
            'hero_slides.*.image_file.max' => 'Each hero slider image must be no larger than 10 MB.',
            'items.array' => 'The homepage item list could not be read. Please refresh the page and try again.',
            'items.*.category_id.integer' => 'One of the selected homepage categories is invalid. Please select it again.',
            'items.*.category_id.exists' => 'One of the selected categories is no longer available. Please choose another category.',
            'items.*.icon.max' => 'An item icon or set of initials cannot be longer than 20 characters.',
            'items.*.title.max' => 'An item title cannot be longer than 255 characters.',
            'items.*.subtitle.max' => 'An item subtitle cannot be longer than 255 characters.',
            'items.*.description.max' => 'An item description cannot be longer than 2,000 characters.',
            'items.*.label.max' => 'An item button label cannot be longer than 160 characters.',
            'items.*.image_alt.max' => 'An item image description cannot be longer than 255 characters.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'hero_slides.*.image_url' => 'hero slider image URL',
            'hero_slides.*.image_alt' => 'hero slider image description',
            'hero_slides.*.image_file' => 'hero slider image',
            'items.*.icon' => 'item icon or initials',
            'items.*.title' => 'item title',
            'items.*.subtitle' => 'item subtitle',
            'items.*.description' => 'item description',
            'items.*.url' => 'item link',
            'items.*.label' => 'item button label',
            'items.*.image_url' => 'item image URL',
            'items.*.image_alt' => 'item image description',
            'items.*.category_id' => 'selected category',
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
            $itemFields = $definition['item_fields'] ?? [];

            if (in_array('buttons', $fields, true)) {
                if (filled($this->input('primary_label')) && blank($this->input('primary_url'))) {
                    $validator->errors()->add('primary_url', 'Enter the primary button destination or remove the label.');
                }

                if (filled($this->input('secondary_label')) && blank($this->input('secondary_url'))) {
                    $validator->errors()->add('secondary_url', 'Enter the secondary button destination or remove the label.');
                }
            }

            if (in_array('category_id', $itemFields, true)) {
                $this->addDuplicateCategoryErrors($validator);
            }
        });
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->safe()->except(['image_file', 'image_url', 'remove_image', 'mobile_image_file', 'mobile_image_url', 'remove_mobile_image', 'hero_slides']);

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

    private function addDuplicateCategoryErrors(Validator $validator): void
    {
        $positionsByCategory = collect((array) $this->input('items', []))
            ->map(function ($item, int $index): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $categoryId = (int) ($item['category_id'] ?? 0);

                return $categoryId > 0
                    ? ['category_id' => $categoryId, 'position' => $index + 1]
                    : null;
            })
            ->filter()
            ->groupBy('category_id')
            ->map(fn (Collection $rows): array => $rows->pluck('position')->map(fn ($position): int => (int) $position)->values()->all())
            ->filter(fn (array $positions): bool => count($positions) > 1);

        if ($positionsByCategory->isEmpty()) {
            return;
        }

        $categoryLabels = $this->categoryLabels($positionsByCategory->keys()->map(fn ($id): int => (int) $id)->all());

        foreach ($positionsByCategory as $categoryId => $positions) {
            $label = $categoryLabels[(int) $categoryId] ?? 'Category #'.(int) $categoryId;
            $validator->errors()->add(
                'items',
                sprintf(
                    '“%s” is listed more than once (items %s). Keep it only once or choose a different category.',
                    $label,
                    $this->formatPositions($positions),
                ),
            );
        }
    }

    /**
     * @param array<int, int> $categoryIds
     * @return array<int, string>
     */
    private function categoryLabels(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        return Category::query()
            ->with('ancestors')
            ->whereKey($categoryIds)
            ->get()
            ->mapWithKeys(function (Category $category): array {
                $parts = $category->ancestors
                    ->sortByDesc(fn (Category $ancestor): int => (int) ($ancestor->pivot?->depth ?? 0))
                    ->map(fn (Category $ancestor): string => (string) ($ancestor->short_title ?: $ancestor->displayLabel()))
                    ->filter()
                    ->values()
                    ->all();

                $parts[] = (string) ($category->short_title ?: $category->displayLabel());

                return [(int) $category->id => implode(' › ', array_values(array_unique(array_filter($parts))))];
            })
            ->all();
    }

    /** @param array<int, int> $positions */
    private function formatPositions(array $positions): string
    {
        $positions = array_values(array_unique(array_map('intval', $positions)));

        if (count($positions) <= 1) {
            return (string) ($positions[0] ?? '');
        }

        $last = array_pop($positions);

        return implode(', ', $positions).' and '.$last;
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
