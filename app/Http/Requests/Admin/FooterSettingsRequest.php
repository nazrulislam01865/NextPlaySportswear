<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;

class FooterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],

            'columns' => ['nullable', 'array', 'max:6'],
            'columns.*.enabled' => ['nullable', 'boolean'],
            'columns.*.title' => ['nullable', 'string', 'max:120'],
            'columns.*.items' => ['nullable', 'array', 'max:12'],
            'columns.*.items.*.enabled' => ['nullable', 'boolean'],
            'columns.*.items.*.label' => ['nullable', 'string', 'max:120'],
            'columns.*.items.*.url' => $this->urlRules(),
            'columns.*.items.*.icon' => ['nullable', 'string', 'max:2048'],
            'columns.*.items.*.icon_file' => $this->iconRules(),
            'columns.*.items.*.remove_icon' => ['nullable', 'boolean'],

            'club_enabled' => ['nullable', 'boolean'],
            'club_title' => ['nullable', 'string', 'max:255'],
            'club_button_label' => ['nullable', 'string', 'max:120'],
            'club_button_url' => $this->urlRules(),

            'social_enabled' => ['nullable', 'boolean'],
            'social_label' => ['nullable', 'string', 'max:120'],
            'social_links' => ['nullable', 'array', 'max:12'],
            'social_links.*.enabled' => ['nullable', 'boolean'],
            'social_links.*.label' => ['nullable', 'string', 'max:120'],
            'social_links.*.url' => $this->urlRules(),
            'social_links.*.icon' => ['nullable', 'string', 'max:2048'],
            'social_links.*.icon_file' => $this->iconRules(),
            'social_links.*.remove_icon' => ['nullable', 'boolean'],

            'copyright' => ['nullable', 'string', 'max:255'],
            'legal_links' => ['nullable', 'array', 'max:10'],
            'legal_links.*.enabled' => ['nullable', 'boolean'],
            'legal_links.*.label' => ['nullable', 'string', 'max:120'],
            'legal_links.*.url' => $this->urlRules(),
            'legal_links.*.icon' => ['nullable', 'string', 'max:2048'],
            'legal_links.*.icon_file' => $this->iconRules(),
            'legal_links.*.remove_icon' => ['nullable', 'boolean'],

            'payments_enabled' => ['nullable', 'boolean'],
            'payments_label' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return [
            'contact' => [
                'address' => $this->cleanScalar('address'),
                'email' => $this->cleanScalar('email'),
                'phone' => $this->cleanScalar('phone'),
            ],
            'columns' => $this->cleanColumns((array) $this->input('columns', [])),
            'club' => [
                'enabled' => $this->boolean('club_enabled'),
                'title' => $this->cleanScalar('club_title'),
                'button_label' => $this->cleanScalar('club_button_label'),
                'button_url' => $this->cleanScalar('club_button_url'),
            ],
            'social' => [
                'enabled' => $this->boolean('social_enabled'),
                'label' => $this->cleanScalar('social_label'),
                'links' => $this->cleanLinks((array) $this->input('social_links', [])),
            ],
            'legal' => [
                'copyright' => $this->cleanScalar('copyright'),
                'links' => $this->cleanLinks((array) $this->input('legal_links', [])),
            ],
            'payments' => [
                'enabled' => $this->boolean('payments_enabled'),
                'label' => $this->cleanScalar('payments_label'),
            ],
        ];
    }

    /** @return array<int, mixed> */
    private function urlRules(): array
    {
        return ['nullable', 'string', 'max:2048', new SafePublicUrl()];
    }

    /** @return array<int, mixed> */
    private function iconRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:png,jpg,jpeg,webp',
            'max:2048',
            'dimensions:max_width=1024,max_height=1024',
        ];
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanColumns(array $rows): array
    {
        $clean = [];

        foreach ($rows as $rowKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = $this->cleanValue($row['title'] ?? null);
            if ($title === null) {
                continue;
            }

            $clean[] = [
                '_row_key' => (string) $rowKey,
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'title' => $title,
                'items' => $this->cleanLinks((array) ($row['items'] ?? [])),
            ];
        }

        return $clean;
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanLinks(array $rows): array
    {
        $clean = [];

        foreach ($rows as $rowKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $this->cleanValue($row['label'] ?? null);
            $url = $this->cleanValue($row['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $clean[] = [
                '_row_key' => (string) $rowKey,
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'label' => $label,
                'url' => $url,
                'icon' => $this->cleanValue($row['icon'] ?? null),
            ];
        }

        return $clean;
    }

    private function cleanScalar(string $field): ?string
    {
        return $this->cleanValue($this->input($field));
    }

    private function cleanValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
