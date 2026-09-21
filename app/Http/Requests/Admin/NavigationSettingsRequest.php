<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NavigationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'navigation' => ['required', 'array'],
            'navigation.items' => ['required', 'array', 'max:20'],
            'navigation.items.*.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.label' => ['required', 'string', 'max:120'],
            'navigation.items.*.url' => $this->urlRules(required: true),
            'navigation.items.*.target' => ['nullable', Rule::in(['_self', '_blank'])],
            'navigation.items.*.mega_menu' => ['nullable', 'array'],
            'navigation.items.*.mega_menu.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.top_choices' => ['nullable', 'array'],
            'navigation.items.*.mega_menu.top_choices.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.top_choices.eyebrow' => ['nullable', 'string', 'max:120'],
            'navigation.items.*.mega_menu.top_choices.links' => ['nullable', 'array', 'max:16'],
            'navigation.items.*.mega_menu.top_choices.links.*.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.top_choices.links.*.label' => ['nullable', 'string', 'max:120'],
            'navigation.items.*.mega_menu.top_choices.links.*.url' => $this->urlRules(),
            'navigation.items.*.mega_menu.columns' => ['nullable', 'array', 'max:4'],
            'navigation.items.*.mega_menu.columns.*.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.columns.*.title' => ['nullable', 'string', 'max:120'],
            'navigation.items.*.mega_menu.columns.*.links' => ['nullable', 'array', 'max:16'],
            'navigation.items.*.mega_menu.columns.*.links.*.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.columns.*.links.*.label' => ['nullable', 'string', 'max:120'],
            'navigation.items.*.mega_menu.columns.*.links.*.url' => $this->urlRules(),
            'navigation.items.*.mega_menu.promo' => ['nullable', 'array'],
            'navigation.items.*.mega_menu.promo.enabled' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.promo.image' => ['nullable', 'string', 'max:2048'],
            'navigation.items.*.mega_menu.promo.image_file' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
                'dimensions:max_width=4000,max_height=3000',
            ],
            'navigation.items.*.mega_menu.promo.remove_image' => ['nullable', 'boolean'],
            'navigation.items.*.mega_menu.promo.alt' => ['nullable', 'string', 'max:255'],
            'navigation.items.*.mega_menu.promo.label' => ['nullable', 'string', 'max:120'],
            'navigation.items.*.mega_menu.promo.url' => $this->urlRules(),
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return [
            'items' => $this->cleanNavigationItems(
                (array) data_get($this->input('navigation', []), 'items', [])
            ),
        ];
    }

    /** @return array<int, mixed> */
    private function urlRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:2048', new SafePublicUrl()];
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanNavigationItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $rowKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $this->cleanValue($row['label'] ?? null);
            $url = $this->cleanValue($row['url'] ?? null);
            if ($label === null || $url === null) {
                continue;
            }

            $mega = is_array($row['mega_menu'] ?? null) ? $row['mega_menu'] : [];
            $topChoices = is_array($mega['top_choices'] ?? null) ? $mega['top_choices'] : [];
            $promo = is_array($mega['promo'] ?? null) ? $mega['promo'] : [];
            $target = (string) ($row['target'] ?? '_self');

            $items[] = [
                '_row_key' => (string) $rowKey,
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'label' => $label,
                'url' => $url,
                'target' => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
                'mega_menu' => [
                    'enabled' => filter_var($mega['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'top_choices' => [
                        'enabled' => filter_var($topChoices['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'eyebrow' => $this->cleanValue($topChoices['eyebrow'] ?? null),
                        'links' => $this->cleanLinks((array) ($topChoices['links'] ?? [])),
                    ],
                    'columns' => $this->cleanMegaColumns((array) ($mega['columns'] ?? [])),
                    'promo' => [
                        'enabled' => filter_var($promo['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'image' => $this->cleanValue($promo['image'] ?? null),
                        'alt' => $this->cleanValue($promo['alt'] ?? null),
                        'label' => $this->cleanValue($promo['label'] ?? null),
                        'url' => $this->cleanValue($promo['url'] ?? null),
                    ],
                ],
            ];
        }

        return $items;
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanMegaColumns(array $rows): array
    {
        $columns = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = $this->cleanValue($row['title'] ?? null);
            if ($title === null) {
                continue;
            }

            $columns[] = [
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'title' => $title,
                'links' => $this->cleanLinks((array) ($row['links'] ?? [])),
            ];
        }

        return $columns;
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanLinks(array $rows): array
    {
        $links = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $this->cleanValue($row['label'] ?? null);
            $url = $this->cleanValue($row['url'] ?? null);
            if ($label === null || $url === null) {
                continue;
            }

            $links[] = [
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'label' => $label,
                'url' => $url,
            ];
        }

        return $links;
    }

    private function cleanValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
