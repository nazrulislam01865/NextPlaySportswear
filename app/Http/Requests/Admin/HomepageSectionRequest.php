<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\ApproximateImageAspectRatio;
use App\Rules\SafePublicUrl;
use App\Services\Catalog\HomepageStagedUploadService;
use App\Support\HomepageImageAspectRatios;
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
        $sectionKey = (string) $this->route('key');
        $sectionImageRules = ['nullable', 'bail', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'];
        $itemImageRules = ['nullable', 'bail', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'];

        if ($definition = HomepageImageAspectRatios::forSectionImage($sectionKey)) {
            $sectionImageRules[] = new ApproximateImageAspectRatio($definition);
        }

        if ($definition = HomepageImageAspectRatios::forSectionItem($sectionKey)) {
            $itemImageRules[] = new ApproximateImageAspectRatio($definition);
        }

        return [
            'eyebrow' => ['nullable', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'primary_label' => ['nullable', 'string', 'max:160'],
            'primary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'secondary_label' => ['nullable', 'string', 'max:160'],
            'secondary_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'image_file' => $sectionImageRules,
            'image_upload_token' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.label' => ['nullable', 'string', 'max:160'],
            'items.*.image_path' => ['nullable', 'string', 'max:2048'],
            'items.*.image_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.image_alt' => ['nullable', 'string', 'max:255'],
            'items.*.image_file' => $itemImageRules,
            'items.*.image_upload_token' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'items.*.remove_image' => ['nullable', 'boolean'],
            'items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'settings' => ['nullable', 'array'],
            'settings.default_sport_id' => ['nullable', 'integer', 'exists:categories,id'],
            'settings.quick_links' => ['nullable', 'array', 'size:4'],
            'settings.quick_links.*.id' => ['required_with:settings.quick_links', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'settings.quick_links.*.label' => ['nullable', 'string', 'max:160'],
            'settings.quick_links.*.url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'settings.tabs' => ['nullable', 'array'],
            'settings.tabs.*.label' => ['nullable', 'string', 'max:160'],
            'settings.tabs.*.enabled' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.array' => 'The homepage item list could not be read. Please refresh the page and try again.',
            'items.*.id.regex' => 'Homepage item IDs may contain lowercase letters, numbers, and hyphens only.',
            'items.*.category_id.exists' => 'One of the selected categories is no longer available. Please choose another category.',
            'items.*.image_file.image' => 'Each item upload must be a valid image.',
            'items.*.image_file.mimes' => 'Item images must be JPG, PNG, WebP, or AVIF files.',
            'items.*.image_file.max' => 'Each item image must be no larger than 10 MB.',
            'items.*.image_file.uploaded' => 'The browser could not transfer one of the images through PHP multipart upload. Choose it again; the homepage uploader will stage it automatically.',
            'image_file.max' => 'The image must be no larger than 10 MB.',
            'image_file.uploaded' => 'The browser could not transfer this image through PHP multipart upload. Choose it again; the homepage uploader will stage it automatically.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'primary_url' => trim((string) $this->input('primary_url', '')) ?: null,
            'secondary_url' => trim((string) $this->input('secondary_url', '')) ?: null,
            'image_url' => trim((string) $this->input('image_url', '')) ?: null,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = (string) $this->route('key');
            $definition = HomepageSectionRegistry::definition($key);
            $fields = (array) ($definition['fields'] ?? []);

            $uploads = app(HomepageStagedUploadService::class);
            $userId = (int) ($this->user()?->id ?? 0);
            $sectionToken = trim((string) $this->input('image_upload_token', ''));
            if ($sectionToken !== '') {
                $error = $uploads->validationError($userId, $sectionToken, HomepageImageAspectRatios::forSectionImage($key));
                if ($error !== null) {
                    $validator->errors()->add('image_upload_token', $error);
                }
            }

            $itemDefinition = HomepageImageAspectRatios::forSectionItem($key);
            foreach ((array) $this->input('items', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $token = trim((string) ($item['image_upload_token'] ?? ''));
                if ($token === '') {
                    continue;
                }
                $error = $uploads->validationError($userId, $token, $itemDefinition);
                if ($error !== null) {
                    $validator->errors()->add("items.{$index}.image_upload_token", $error);
                }
            }

            if (in_array('buttons', $fields, true)) {
                if (filled($this->input('primary_label')) && blank($this->input('primary_url'))) {
                    $validator->errors()->add('primary_url', 'Enter the primary button destination or remove the label.');
                }
                if (filled($this->input('secondary_label')) && blank($this->input('secondary_url'))) {
                    $validator->errors()->add('secondary_url', 'Enter the secondary button destination or remove the label.');
                }
            }

            match ($key) {
                'audience' => $this->validateAudience($validator),
                'shop_by_sport' => $this->validateCategoryItems($validator, 'sport'),
                'shop_by_category' => $this->validateCategoryItems($validator, 'category'),
                'best_choices' => $this->validateBestChoices($validator),
                'design_process' => $this->validateDesignProcess($validator),
                default => null,
            };
        });
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->safe()->except([
            'image_file', 'image_upload_token', 'image_url', 'remove_image',
            'items', 'settings', 'sort_order',
        ]);

        foreach (['eyebrow', 'title', 'description', 'primary_label', 'primary_url', 'secondary_label', 'secondary_url', 'image_alt'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = $this->cleanText($data[$field]);
            }
        }

        $key = (string) $this->route('key');
        $data['items'] = $this->cleanItems((array) $this->input('items', []));
        $data['settings'] = $this->cleanSettings($key, (array) $this->input('settings', []));
        $data['is_active'] = $this->boolean('is_active');
        $data['sort_order'] = (int) (HomepageSectionRegistry::definition((string) $this->route('key'))['sort_order'] ?? 0);

        return $data;
    }

    private function validateAudience(Validator $validator): void
    {
        $items = collect((array) $this->input('items', []));
        if ($items->count() !== 3 || $items->pluck('id')->sort()->values()->all() !== ['kids', 'men', 'women']) {
            $validator->errors()->add('items', 'Audience Tiles must contain MEN, WOMEN, and KIDS exactly once.');
        }
    }

    private function validateDesignProcess(Validator $validator): void
    {
        if (count((array) $this->input('items', [])) > 5) {
            $validator->errors()->add('items', 'Design Process supports up to five steps.');
        }
    }

    private function validateBestChoices(Validator $validator): void
    {
        $tabs = (array) $this->input('settings.tabs', []);
        $enabled = collect(['featured', 'popular', 'trending'])
            ->contains(fn (string $key): bool => filter_var($tabs[$key]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN));

        if (! $enabled) {
            $validator->errors()->add('settings.tabs', 'Keep at least one Best Choices tab enabled.');
        }
    }

    private function validateCategoryItems(Validator $validator, string $kind): void
    {
        $positionsByCategory = collect((array) $this->input('items', []))
            ->map(function ($item, int $index): ?array {
                if (! is_array($item)) {
                    return null;
                }
                $categoryId = (int) ($item['category_id'] ?? 0);
                return $categoryId > 0 ? ['category_id' => $categoryId, 'position' => $index + 1] : null;
            })
            ->filter()
            ->groupBy('category_id')
            ->filter(fn (Collection $rows): bool => $rows->count() > 1);

        if ($positionsByCategory->isEmpty()) {
            return;
        }

        $labels = $this->categoryLabels($positionsByCategory->keys()->map(fn ($id): int => (int) $id)->all());
        foreach ($positionsByCategory as $categoryId => $rows) {
            $label = $labels[(int) $categoryId] ?? ucfirst($kind).' #'.(int) $categoryId;
            $positions = $rows->pluck('position')->map(fn ($value): int => (int) $value)->all();
            $validator->errors()->add('items', sprintf('“%s” is listed more than once (items %s). Keep it only once or choose a different %s.', $label, $this->formatPositions($positions), $kind));
        }
    }

    /** @param array<int, int> $categoryIds @return array<int, string> */
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
                    ->filter()->values()->all();
                $parts[] = (string) ($category->short_title ?: $category->displayLabel());
                return [(int) $category->id => implode(' › ', array_values(array_unique(array_filter($parts))))];
            })->all();
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

    /** @return array<int, array<string, mixed>> */
    private function cleanItems(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];
            foreach (['id', 'title', 'subtitle', 'description', 'url', 'label', 'image_path', 'image_url', 'image_alt'] as $field) {
                $value = $this->cleanText($item[$field] ?? null);
                if ($value !== null) {
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

    /** @return array<string, mixed> */
    private function cleanSettings(string $key, array $settings): array
    {
        if ($key === 'shop_by_sport') {
            $quickLinks = collect((array) ($settings['quick_links'] ?? []))
                ->take(4)
                ->map(function ($row): array {
                    $row = is_array($row) ? $row : [];
                    return array_filter([
                        'id' => $this->cleanText($row['id'] ?? null),
                        'label' => $this->cleanText($row['label'] ?? null),
                        'url' => $this->cleanText($row['url'] ?? null),
                    ], fn ($value): bool => $value !== null);
                })->values()->all();

            return [
                'default_sport_id' => (int) ($settings['default_sport_id'] ?? 0) ?: null,
                'quick_links' => $quickLinks,
            ];
        }

        if ($key === 'best_choices') {
            $tabs = [];
            foreach (['featured', 'popular', 'trending'] as $tab) {
                $row = (array) ($settings['tabs'][$tab] ?? []);
                $tabs[$tab] = [
                    'label' => $this->cleanText($row['label'] ?? null) ?: strtoupper($tab),
                    'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            }
            return ['tabs' => $tabs];
        }

        return [];
    }

    private function cleanText(mixed $value): ?string
    {
        $value = trim(strip_tags((string) $value));
        return $value === '' ? null : $value;
    }
}
