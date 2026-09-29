<?php

namespace App\Http\Requests\Admin;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class AboutPageRequest extends FormRequest
{
    private const SERVICE_IDS = ['custom-teamwear', 'sportswear-gear', 'bulk-orders'];
    private const PROCESS_IDS = ['choose-product', 'personalise', 'review-details', 'place-order'];
    private const GALLERY_IDS = ['team', 'fabric', 'number', 'celebration'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'hero' => ['required', 'array:eyebrow,title,description'],
            'hero.eyebrow' => ['required', 'string', 'max:120'],
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],

            'introduction' => ['required', 'array:title,description,image_alt'],
            'introduction.title' => ['required', 'string', 'max:255'],
            'introduction.description' => ['required', 'string', 'max:1000'],
            'introduction.image_alt' => ['required', 'string', 'max:255'],
            'introduction_image' => $this->imageRules(10240),
            'remove_introduction_image' => ['nullable', 'boolean'],

            'what_we_do' => ['required', 'array:title,cards'],
            'what_we_do.title' => ['required', 'string', 'max:255'],
            'what_we_do.cards' => ['required', 'array', 'size:3'],
            'what_we_do.cards.*' => ['required', 'array:id,title,description,icon_alt'],
            'what_we_do.cards.*.id' => ['required', 'string', 'max:100'],
            'what_we_do.cards.*.title' => ['required', 'string', 'max:255'],
            'what_we_do.cards.*.description' => ['required', 'string', 'max:1000'],
            'what_we_do.cards.*.icon_alt' => ['required', 'string', 'max:255'],

            'how_we_work' => ['required', 'array:title,steps'],
            'how_we_work.title' => ['required', 'string', 'max:255'],
            'how_we_work.steps' => ['required', 'array', 'size:4'],
            'how_we_work.steps.*' => ['required', 'array:id,number,title,description,icon_alt'],
            'how_we_work.steps.*.id' => ['required', 'string', 'max:100'],
            'how_we_work.steps.*.number' => ['required', 'string', 'max:10'],
            'how_we_work.steps.*.title' => ['required', 'string', 'max:255'],
            'how_we_work.steps.*.description' => ['required', 'string', 'max:1000'],
            'how_we_work.steps.*.icon_alt' => ['required', 'string', 'max:255'],

            'gallery' => ['required', 'array:items'],
            'gallery.items' => ['required', 'array', 'size:4'],
            'gallery.items.*' => ['required', 'array:id,image_alt'],
            'gallery.items.*.id' => ['required', 'string', 'max:100'],
            'gallery.items.*.image_alt' => ['required', 'string', 'max:255'],

            'cta' => ['required', 'array:eyebrow,title,primary_label,primary_url,secondary_label,secondary_url'],
            'cta.eyebrow' => ['required', 'string', 'max:120'],
            'cta.title' => ['required', 'string', 'max:255'],
            'cta.primary_label' => ['required', 'string', 'max:100'],
            'cta.primary_url' => $this->destinationRules(),
            'cta.secondary_label' => ['required', 'string', 'max:100'],
            'cta.secondary_url' => $this->destinationRules(),

            'help' => ['required', 'array:title,description,icon_alt,button_label,button_url'],
            'help.title' => ['required', 'string', 'max:255'],
            'help.description' => ['required', 'string', 'max:1000'],
            'help.icon_alt' => ['required', 'string', 'max:255'],
            'help.button_label' => ['required', 'string', 'max:100'],
            'help.button_url' => $this->destinationRules(),
            'help_icon' => $this->imageRules(2048),
            'remove_help_icon' => ['nullable', 'boolean'],

            'seo' => ['required', 'array:title,description'],
            'seo.title' => ['required', 'string', 'max:255'],
            'seo.description' => ['required', 'string', 'max:500'],
        ];

        for ($i = 0; $i < 3; $i++) {
            $rules['service_icon_'.$i] = $this->imageRules(2048);
            $rules['remove_service_icon_'.$i] = ['nullable', 'boolean'];
        }

        for ($i = 0; $i < 4; $i++) {
            $rules['process_icon_'.$i] = $this->imageRules(2048);
            $rules['remove_process_icon_'.$i] = ['nullable', 'boolean'];
            $rules['gallery_image_'.$i] = $this->imageRules(10240);
            $rules['remove_gallery_image_'.$i] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStableIds($validator, 'what_we_do.cards', self::SERVICE_IDS);
            $this->validateStableIds($validator, 'how_we_work.steps', self::PROCESS_IDS);
            $this->validateStableIds($validator, 'gallery.items', self::GALLERY_IDS);
        });
    }

    /** @return array<string, mixed> */
    public function validatedContent(): array
    {
        return Arr::only($this->validated(), [
            'hero',
            'introduction',
            'what_we_do',
            'how_we_work',
            'gallery',
            'cta',
            'help',
            'seo',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $content = [];
        foreach (['hero', 'introduction', 'what_we_do', 'how_we_work', 'gallery', 'cta', 'help', 'seo'] as $key) {
            if ($this->has($key) && is_array($this->input($key))) {
                $content[$key] = $this->trimValues($this->input($key));
            }
        }

        $removeFlags = ['remove_introduction_image', 'remove_help_icon'];
        for ($i = 0; $i < 3; $i++) {
            $removeFlags[] = 'remove_service_icon_'.$i;
        }
        for ($i = 0; $i < 4; $i++) {
            $removeFlags[] = 'remove_process_icon_'.$i;
            $removeFlags[] = 'remove_gallery_image_'.$i;
        }

        foreach ($removeFlags as $flag) {
            $content[$flag] = $this->boolean($flag);
        }

        $this->merge($content);
    }

    /** @return array<int, mixed> */
    private function imageRules(int $maxKilobytes): array
    {
        return [
            'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp,avif',
            'mimetypes:image/jpeg,image/png,image/webp,image/avif',
            'max:'.$maxKilobytes,
        ];
    }

    /** @return array<int, mixed> */
    private function destinationRules(): array
    {
        return [
            'required',
            'string',
            'max:2048',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! $this->isAllowedDestination($value)) {
                    $fail('The '.$attribute.' must be a relative site path or a valid HTTP/HTTPS URL.');
                }
            },
        ];
    }

    private function isAllowedDestination(?string $value): bool
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '#')) {
            return false;
        }

        return PublicUrl::isAllowed($value);
    }

    /** @param array<int, string> $expected */
    private function validateStableIds(Validator $validator, string $key, array $expected): void
    {
        $items = $this->input($key, []);
        if (! is_array($items) || count($items) !== count($expected)) {
            return;
        }

        $ids = array_map(
            static fn (mixed $item): ?string => is_array($item) && isset($item['id']) ? (string) $item['id'] : null,
            $items
        );

        if ($ids !== $expected) {
            $validator->errors()->add($key, 'The fixed About page slots cannot be added, removed, duplicated, or reordered.');
        }
    }

    private function trimValues(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $value[$childKey] = $this->trimValues($childValue, (string) $childKey);
            }

            return $value;
        }

        if (is_string($value) && $key !== 'id') {
            return trim($value);
        }

        return $value;
    }
}
